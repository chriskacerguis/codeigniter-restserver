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
        $this->restConfig = config(RestConfig::class);
        $this->format = $this->restConfig->defaultFormat;
    }

    protected function respondData($data, int $status = 200): ResponseInterface
    {
        return $this->respond($data, $status);
    }

    protected function formatData($data, ?string $format = null): string
    {
        $format = $format ?? $this->restConfig->defaultFormat;
        $formatter = Format::factory($data);
        $method = 'to_'.$format;
        if (method_exists($formatter, $method)) {
            return $formatter->{$method}();
        }
        return $formatter->to_json();
    }
}
