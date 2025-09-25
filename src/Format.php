<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer;

use Exception;

final class Format
{
    public const ARRAY_FORMAT      = 'array';
    public const CSV_FORMAT        = 'csv';
    public const JSON_FORMAT       = 'json';
    public const HTML_FORMAT       = 'html';
    public const PHP_FORMAT        = 'php';
    public const SERIALIZED_FORMAT = 'serialized';
    public const XML_FORMAT        = 'xml';
    public const DEFAULT_FORMAT    = self::JSON_FORMAT;

    private mixed $data = [];

    public function __construct(mixed $data = null, ?string $fromType = null)
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
    }

    /**
     * @return static
     */
    /**
     * @return static
     */
    public static function factory(mixed $data, ?string $fromType = null): static
    {
        return new static($data, $fromType);
    }

    private static function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_null($value)) {
            return '';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        return $json === false ? '' : $json;
    }

    // New PSR-12 camelCase API ---------------------------------------------

    /**
     * @return array<mixed>
     */
    public function toArray(mixed $data = null): array
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        if (!is_iterable($data)) {
            $data = (array) $data;
        }
        $array = [];
        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                /** @var int|string $key */
                $array[$key] = $this->toArray($value);
            } else {
                /** @var int|string $key */
                $array[$key] = $value;
            }
        }
        return $array;
    }

    public function toXml(mixed $data = null, ?\SimpleXMLElement $structure = null, string $basenode = 'xml'): string
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }

        if ($structure === null) {
            $structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
            if ($structure === false) {
                $structure = new \SimpleXMLElement("<$basenode />");
            }
        }
        if (!is_array($data) && !is_object($data)) {
            $data = (array) $data;
        }

        foreach ((array) $data as $key => $value) {
            if (is_bool($value)) {
                $value = (int) $value;
            }
            if (is_numeric($key)) {
                $singular = function_exists('singular') ? singular($basenode) : rtrim($basenode, 's');
                $key = ($singular !== $basenode) ? $singular : 'item';
            }
            $keyStr = preg_replace('/[^a-z_\-0-9]/i', '', self::stringify($key));
            $key = $keyStr === null ? 'item' : $keyStr;
            if ($key === '_attributes' && (is_array($value) || is_object($value))) {
                $attributes = $value;
                if (is_object($attributes)) {
                    $attributes = get_object_vars($attributes);
                }

                foreach ($attributes as $attributeName => $attributeValue) {
                    $structure->addAttribute(
                        self::stringify($attributeName),
                        self::stringify($attributeValue)
                    );
                }
            } elseif (is_array($value) || is_object($value)) {
                $node = $structure->addChild($key);
                $this->toXml($value, $node, $key);
            } else {
                $value = htmlspecialchars(
                    html_entity_decode(self::stringify($value), ENT_QUOTES, 'UTF-8'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $structure->addChild($key, self::stringify($value));
            }
        }
        $xml = $structure->asXML();
        return $xml === false ? '' : (string) $xml;
    }

    public function toHtml(mixed $data = null): string
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        if (!is_array($data)) {
            $data = (array) $data;
        }
        if (isset($data[0]) && is_array($data[0]) && count($data) !== count($data, COUNT_RECURSIVE)) {
            $headings = array_keys((array) $data[0]);
        } else {
            $headings = array_keys((array) $data);
            $data = [$data];
        }
        $html = '<table>';
        $html .= '<thead><tr>';
        foreach ($headings as $h) {
            $html .= '<th>' . htmlspecialchars(self::stringify($h), ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($headings as $h) {
                $key = is_int($h) ? (string) $h : (string) $h;
                $val = is_array($row) && array_key_exists($key, $row) ? $row[$key] : '';
                $html .= '<td>' . htmlspecialchars(self::stringify($val), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    public function toCsv(mixed $data = null, ?string $delimiter = ',', ?string $enclosure = '"'): string
    {
        $handle = fopen('php://temp/maxmemory:1048576', 'w');
        if ($handle === false) {
            return '';
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
        if (isset($data[0]) && is_array($data[0]) && count($data) !== count($data, COUNT_RECURSIVE)) {
            $headings = array_keys((array) $data[0]);
        } else {
            $headings = array_keys((array) $data);
            $data = [$data];
        }
        fputcsv($handle, $headings, $delimiter, $enclosure);
        foreach ((array) $data as $record) {
            if (!is_array($record)) {
                break;
            }
            // Safely cast values to string
            $record = array_map(
                static function ($v): string {
                    return self::stringify($v);
                },
                $record
            );
            fputcsv($handle, $record, $delimiter, $enclosure);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        $csvStr = is_string($csv) ? $csv : '';
        $csvStr = mb_convert_encoding($csvStr, 'UTF-16LE', 'UTF-8');
        return $csvStr;
    }

    public function toJson(mixed $data = null): string
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        // JSONP is intentionally not supported; always return JSON
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function toSerialized(mixed $data = null): string
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        return serialize($data);
    }

    public function toPhp(mixed $data = null): string
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->data;
        }
        return var_export($data, true);
    }

    /**
     * @return array<mixed>
     */
    protected function fromXml(string $data): array
    {
        if ($data === '') {
            return [];
        }
        $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            return [];
        }
        return (array) $xml;
    }

    /**
     * @return array<mixed>
     */
    protected function fromCsv(string $data, ?string $delimiter = ',', ?string $enclosure = '"'): array
    {
        if ($delimiter === null) {
            $delimiter = ',';
        }
        if ($enclosure === null) {
            $enclosure = '"';
        }
        return str_getcsv($data, $delimiter, $enclosure);
    }

    protected function fromJson(string $data): mixed
    {
        return json_decode(trim($data));
    }

    protected function fromSerialize(string $data): mixed
    {
        return unserialize(trim($data), ['allowed_classes' => false]);
    }

    protected function fromPhp(string $data): string
    {
        return trim($data);
    }
}
