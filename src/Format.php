<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer;

use Exception;

class Format
{
    public const ARRAY_FORMAT      = 'array';
    public const CSV_FORMAT        = 'csv';
    public const JSON_FORMAT       = 'json';
    public const HTML_FORMAT       = 'html';
    public const PHP_FORMAT        = 'php';
    public const SERIALIZED_FORMAT = 'serialized';
    public const XML_FORMAT        = 'xml';
    public const DEFAULT_FORMAT    = self::JSON_FORMAT;

    private $data = [];
    private $fromType = null;

    public function __construct($data = null, $fromType = null)
    {
        if ($fromType !== null) {
            // Prefer new camelCase converters, fall back to legacy _from_* methods
            $normalized = str_replace(['-', '_'], ' ', (string) $fromType);
            $camel = 'from' . str_replace(' ', '', ucwords($normalized));
            if (method_exists($this, $camel)) {
                $data = $this->{$camel}($data);
            } elseif (method_exists($this, '_from_' . $fromType)) {
                $data = $this->{'_from_' . $fromType}($data);
            } else {
                throw new Exception('Format class does not support conversion from "' . $fromType . '".');
            }
        }
        $this->data = $data;
        $this->fromType = $fromType;
    }

    public static function factory($data, $fromType = null)
    {
        return new static($data, $fromType);
    }

    // New PSR-12 camelCase API ---------------------------------------------

    public function toArray($data = null)
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        if (!is_array($data)) {
            $data = (array) $data;
        }
        $array = [];
        foreach ((array) $data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $array[$key] = $this->toArray($value);
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    public function toXml($data = null, $structure = null, $basenode = 'xml')
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }

        if ($structure === null) {
            $structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
        }
        if (!is_array($data) && !is_object($data)) {
            $data = (array) $data;
        }

        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = (int) $value;
            }
            if (is_numeric($key)) {
                $singular = function_exists('singular') ? singular($basenode) : rtrim($basenode, 's');
                $key = ($singular !== $basenode) ? $singular : 'item';
            }
            $key = preg_replace('/[^a-z_\-0-9]/i', '', (string) $key);
            if ($key === '_attributes' && (is_array($value) || is_object($value))) {
                $attributes = $value;
                if (is_object($attributes)) {
                    $attributes = get_object_vars($attributes);
                }

                foreach ($attributes as $attributeName => $attributeValue) {
                    $structure->addAttribute($attributeName, (string) $attributeValue);
                }
            } elseif (is_array($value) || is_object($value)) {
                $node = $structure->addChild($key);
                $this->toXml($value, $node, $key);
            } else {
                $value = htmlspecialchars(
                    html_entity_decode((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $structure->addChild($key, $value);
            }
        }
        return $structure->asXML();
    }

    public function toHtml($data = null)
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        if (!is_array($data)) {
            $data = (array) $data;
        }
        if (isset($data[0]) && count($data) !== count($data, COUNT_RECURSIVE)) {
            $headings = array_keys($data[0]);
        } else {
            $headings = array_keys($data);
            $data = [$data];
        }
        $html = '<table>';
        $html .= '<thead><tr>';
        foreach ($headings as $h) {
            $html .= '<th>' . htmlspecialchars((string) $h, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($headings as $h) {
                $val = $row[$h] ?? '';
                $html .= '<td>' . htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    public function toCsv($data = null, $delimiter = ',', $enclosure = '"')
    {
        $handle = fopen('php://temp/maxmemory:1048576', 'w');
        if ($handle === false) {
            return null;
        }
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        if ($delimiter === null) {
            $delimiter = ',';
        }
        if ($enclosure === null) {
            $enclosure = '"';
        }
        if (!is_array($data)) {
            $data = (array) $data;
        }
        if (isset($data[0]) && count($data) !== count($data, COUNT_RECURSIVE)) {
            $headings = array_keys($data[0]);
        } else {
            $headings = array_keys($data);
            $data = [$data];
        }
        fputcsv($handle, $headings, $delimiter, $enclosure);
        foreach ($data as $record) {
            if (!is_array($record)) {
                break;
            }
            $record = @array_map('strval', $record);
            fputcsv($handle, $record, $delimiter, $enclosure);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        $csv = mb_convert_encoding((string) $csv, 'UTF-16LE', 'UTF-8');
        return $csv;
    }

    public function toJson($data = null)
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        $callback = null;
        if (empty($callback)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        if (preg_match('/^[a-z_\$][a-z0-9\$_]*(\.[a-z_\$][a-z0-9\$_]*)*$/i', (string) $callback)) {
            return $callback . '(' . json_encode($data, JSON_UNESCAPED_UNICODE) . ');';
        }
        $data['warning'] = 'INVALID JSONP CALLBACK: ' . $callback;
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function toSerialized($data = null)
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        return serialize($data);
    }

    public function toPhp($data = null)
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        return var_export($data, true);
    }

    protected function fromXml($data)
    {
        return $data ? (array) simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA) : [];
    }

    protected function fromCsv($data, $delimiter = ',', $enclosure = '"')
    {
        if ($delimiter === null) {
            $delimiter = ',';
        }
        if ($enclosure === null) {
            $enclosure = '"';
        }
        return str_getcsv((string) $data, $delimiter, $enclosure);
    }

    protected function fromJson($data)
    {
        return json_decode(trim((string) $data));
    }

    protected function fromSerialize($data)
    {
        return unserialize(trim((string) $data), ['allowed_classes' => false]);
    }

    protected function fromPhp($data)
    {
        return trim((string) $data);
    }
}
