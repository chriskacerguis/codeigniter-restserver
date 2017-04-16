<?php

$grammarFileToName = [
    __DIR__ . '/php5.y' => 'Php5',
    __DIR__ . '/php7.y' => 'Php7',
];

$tokensFile     = __DIR__ . '/tokens.y';
$tokensTemplate = __DIR__ . '/tokens.template';
$skeletonFile   = __DIR__ . '/parser.template';
$tmpGrammarFile = __DIR__ . '/tmp_parser.phpy';
$tmpResultFile  = __DIR__ . '/tmp_parser.php';
$resultDir = __DIR__ . '/../lib/PhpParser/Parser';
$tokensResultsFile = $resultDir . '/Tokens.php';

// check for kmyacc.exe binary in this directory, otherwise fall back to global name
$kmyacc = __DIR__ . '/kmyacc.exe';
if (!file_exists($kmyacc)) {
    $kmyacc = 'kmyacc';
}

$options = array_flip($argv);
$optionDebug = isset($options['--debug']);
$optionKeepTmpGrammar = isset($options['--keep-tmp-grammar']);

///////////////////////////////
/// Utility regex constants ///
///////////////////////////////

const LIB = '(?(DEFINE)
    (?<singleQuotedString>\'[^\\\\\']*+(?:\\\\.[^\\\\\']*+)*+\')
    (?<doubleQuotedString>"[^\\\\"]*+(?:\\\\.[^\\\\"]*+)*+")
    (?<string>(?&singleQuotedString)|(?&doubleQuotedString))
    (?<comment>/\*[^*]*+(?:\*(?!/)[^*]*+)*+\*/)
    (?<code>\{[^\'"/{}]*+(?:(?:(?&string)|(?&comment)|(?&code)|/)[^\'"/{}]*+)*+})
)';

const PARAMS = '\[(?<params>[^[\]]*+(?:\[(?&params)\][^[\]]*+)*+)\]';
const ARGS   = '\((?<args>[^()]*+(?:\((?&args)\)[^()]*+)*+)\)';

///////////////////
/// Main script ///
///////////////////

$tokens = file_get_contents($tokensFile);

foreach ($grammarFileToName as $grammarFile => $name) {
    echo "Building temporary $name grammar file.\n";

    $grammarCode = file_get_contents($grammarFile);
    $grammarCode = str_replace('%tokens', $tokens, $grammarCode);

    $grammarCode = resolveNodes($grammarCode);
    $grammarCode = resolveMacros($grammarCode);
    $grammarCode = resolveStackAccess($grammarCode);

    file_put_contents($tmpGrammarFile, $grammarCode);

    $additionalArgs = $optionDebug ? '-t -v' : '';

    echo "Building $name parser.\n";
    $output = trim(shell_exec("$kmyacc $additionalArgs -l -m $skeletonFile -p $name $tmpGrammarFile 2>&1"));
    echo "Output: \"$output\"\n";

    $resultCode = file_get_contents($tmpResultFile);
    $resultCode = removeTrailingWhitespace($resultCode);

    ensureDirExists($resultDir);
    file_put_contents("$resultDir/$name.php", $resultCode);
    unlink($tmpResultFile);

    echo "Building token definition.\n";
    $output = trim(shell_exec("$kmyacc -l -m $tokensTemplate $tmpGrammarFile 2>&1"));
    assert($output === '');
    rename($tmpResultFile, $tokensResultsFile);

    if (!$optionKeepTmpGrammar) {
        unlink($tmpGrammarFile);
    }
}

///////////////////////////////
/// Preprocessing functions ///
///////////////////////////////

function resolveNodes($code) {
    return preg_replace_callback(
        '~\b(?<name>[A-Z][a-zA-Z_\\\\]++)\s*' . PARAMS . '~',
        function($matches) {
            // recurse
            $matches['params'] = resolveNodes($matches['params']);

            $params = magicSplit(
                '(?:' . PARAMS . '|' . ARGS . ')(*SKIP)(*FAIL)|,',
                $matches['params']
            );

            $paramCode = '';
            foreach ($params as $param) {
                $paramCode .= $param . ', ';
            }

            return 'new ' . $matches['name'] . '(' . $paramCode . 'attributes())';
        },
        $code
    );
}

function resolveMacros($code) {
    return preg_replace_callback(
        '~\b(?<!::|->)(?!array\()(?<name>[a-z][A-Za-z]++)' . ARGS . '~',
        function($matches) {
            // recurse
            $matches['args'] = resolveMacros($matches['args']);

            $name = $matches['name'];
            $args = magicSplit(
                '(?:' . PARAMS . '|' . ARGS . ')(*SKIP)(*FAIL)|,',
                $matches['args']
            );

            if ('attributes' == $name) {
                assertArgs(0, $args, $name);
                return '$this->startAttributeStack[#1] + $this->endAttributes';
            }

            if ('init' == $name) {
                return '$$ = array(' . implode(', ', $args) . ')';
            }

            if ('push' == $name) {
                assertArgs(2, $args, $name);

                return $args[0] . '[] = ' . $args[1] . '; $$ = ' . $args[0];
            }

            if ('pushNormalizing' == $name) {
                assertArgs(2, $args, $name);

                return 'if (is_array(' . $args[1] . ')) { $$ = array_merge(' . $args[0] . ', ' . $args[1] . '); }'
                     . ' else { ' . $args[0] . '[] = ' . $args[1] . '; $$ = ' . $args[0] . '; }';
            }

            if ('toArray' == $name) {
                assertArgs(1, $args, $name);

                return 'is_array(' . $args[0] . ') ? ' . $args[0] . ' : array(' . $args[0] . ')';
            }

            if ('parseVar' == $name) {
                assertArgs(1, $args, $name);

                return 'substr(' . $args[0] . ', 1)';
            }

            if ('parseEncapsed' == $name) {
                assertArgs(3, $args, $name);

                return 'foreach (' . $args[0] . ' as $s) { if ($s instanceof Node\Scalar\EncapsedStringPart) {'
                     . ' $s->value = Node\Scalar\String_::parseEscapeSequences($s->value, ' . $args[1] . ', ' . $args[2] . '); } }';
            }

            if ('parseEncapsedDoc' == $name) {
                assertArgs(2, $args, $name);

                return 'foreach (' . $args[0] . ' as $s) { if ($s instanceof Node\Scalar\EncapsedStringPart) {'
                     . ' $s->value = Node\Scalar\String_::parseEscapeSequences($s->value, null, ' . $args[1] . '); } }'
                     . ' $s->value = preg_replace(\'~(\r\n|\n|\r)\z~\', \'\', $s->value);'
                     . ' if (\'\' === $s->value) array_pop(' . $args[0] . ');';
            }

            if ('makeNop' == $name) {
                assertArgs(2, $args, $name);

                return '$startAttributes = ' . $args[1] . ';'
                . ' if (isset($startAttributes[\'comments\']))'
                . ' { ' . $args[0] . ' = new Stmt\Nop([\'comments\' => $startAttributes[\'comments\']]); }'
                . ' else { ' . $args[0] . ' = null; }';
            }

            if ('strKind' == $name) {
                assertArgs(1, $args, $name);

                return '(' . $args[0] . '[0] === "\'" || (' . $args[0] . '[1] === "\'" && '
                     . '(' . $args[0] . '[0] === \'b\' || ' . $args[0] . '[0] === \'B\')) '
                     . '? Scalar\String_::KIND_SINGLE_QUOTED : Scalar\String_::KIND_DOUBLE_QUOTED)';
            }

            if ('setDocStringAttrs' == $name) {
                assertArgs(2, $args, $name);

                return $args[0] . '[\'kind\'] = strpos(' . $args[1] . ', "\'") === false '
                     . '? Scalar\String_::KIND_HEREDOC : Scalar\String_::KIND_NOWDOC; '
                     . 'preg_match(\'/\A[bB]?<<<[ \t]*[\\\'"]?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\\\'"]?(?:\r\n|\n|\r)\z/\', ' . $args[1] . ', $matches); '
                     . $args[0] . '[\'docLabel\'] = $matches[1];';
            }

            return $matches[0];
        },
        $code
    );
}

function assertArgs($num, $args, $name) {
    if ($num != count($args)) {
        die('Wrong argument count for ' . $name . '().');
    }
}

function resolveStackAccess($code) {
    $code = preg_replace('/\$\d+/', '$this->semStack[$0]', $code);
    $code = preg_replace('/#(\d+)/', '$$1', $code);
    return $code;
}

function removeTrailingWhitespace($code) {
    $lines = explode("\n", $code);
    $lines = array_map('rtrim', $lines);
    return implode("\n", $lines);
}

function ensureDirExists($dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

//////////////////////////////
/// Regex helper functions ///
//////////////////////////////

function regex($regex) {
    return '~' . LIB . '(?:' . str_replace('~', '\~', $regex) . ')~';
}

function magicSplit($regex, $string) {
    $pieces = preg_split(regex('(?:(?&string)|(?&comment)|(?&code))(*SKIP)(*FAIL)|' . $regex), $string);

    foreach ($pieces as &$piece) {
        $piece = trim($piece);
    }

    if ($pieces === ['']) {
        return [];
    }

    return $pieces;
}
