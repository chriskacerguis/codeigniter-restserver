<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use chriskacerguis\RestServer\Config\Rest as RestConfig;
use CodeIgniter\HTTP\ResponseInterface;

class RestController extends ResourceController
{
    use ResponseTrait;

    protected RestConfig $restConfig;
    /** @var array<string,string> */
    protected array $supportedFormats = [
        'json'       => 'application/json',
        'array'      => 'application/json',
        'csv'        => 'text/csv',
        'html'       => 'text/html',
        'jsonp'      => 'application/javascript',
        'php'        => 'text/plain',
        'serialized' => 'application/vnd.php.serialized',
        'xml'        => 'application/xml',
    ];

    public function __construct()
    {
        $cfg = config(RestConfig::class);
        $this->restConfig = $cfg instanceof RestConfig ? $cfg : new RestConfig();
        $this->format = $this->restConfig->defaultFormat;
    }

    protected function respondData(mixed $data, int $status = 200): ResponseInterface
    {
        $resp = $this->respond($data, $status);
        // Our test stub's respond() returns the ResponseInterface instance
        return $resp;
    }

    protected function formatData(mixed $data, ?string $format = null): string
    {
        $format = $format ?? $this->restConfig->defaultFormat;
        $formatter = Format::factory($data);
        // Map format keys to camelCase methods
        $map = [
            'json'       => 'toJson',
            'array'      => 'toArray',
            'csv'        => 'toCsv',
            'html'       => 'toHtml',
            'jsonp'      => 'toJson', // JSONP not supported; fall back to JSON
            'php'        => 'toPhp',
            'serialized' => 'toSerialized',
            'xml'        => 'toXml',
        ];
        $method = $map[$format] ?? 'toJson';
        $out = $formatter->{$method}();
        if (is_string($out)) {
            return $out;
        }
        // Fallback for toArray or other non-string outputs
        return Format::factory($out)->toJson();
    }
}
