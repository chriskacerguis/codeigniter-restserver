<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Config;

use CodeIgniter\Config\BaseConfig;

class Rest extends BaseConfig
{
    // Protocol
    public bool $forceHTTPS = false;

    // Output formats
    public string $defaultFormat = 'json';
    /** @var array<string> */
    public array $supportedFormats = [
        'json', 'array', 'csv', 'html', 'jsonp', 'php', 'serialized', 'xml',
    ];

    // Response fields
    public string $statusFieldName = 'status';
    public string $messageFieldName = 'error';

    // Request emulation
    public bool $enableEmulateRequest = true;

    // Realm & Auth
    public string $realm = 'REST API';
    /** @var false|'basic'|'digest'|'session' */
    public $auth = false;
    /** @var ''|'ldap'|'library' */
    public string $authSource = '';
    /** @var array<string,string> */
    public array $validLogins = ['admin' => '1234'];

    // Allow Authentication and API Keys
    public bool $allowAuthAndKeys = true;
    public bool $strictApiAndAuth = true;

    // Library auth hooks
    public string $authLibraryClass = '';
    public string $authLibraryFunction = '';

    // IP lists
    public bool $ipWhitelistEnabled = false;
    public string $ipWhitelist = '';
    public bool $ipBlacklistEnabled = false;
    public string $ipBlacklist = '';

    // Exceptions
    public bool $handleExceptions = true;

    // Database group
    public string $databaseGroup = 'default';

    // Keys
    public bool $enableKeys = false;
    public string $keysTable = 'keys';
    public string $keyColumn = 'key';
    public bool $keysExpire = false;
    public string $keysExpiryColumn = 'expires';
    public int $keyLength = 40;
    public string $keyHeaderName = 'X-API-KEY';
    /**
     * Override the model class used to validate API keys (for testing or customization)
     *
     * @var class-string<\chriskacerguis\RestServer\Models\KeyModel>
     */
    public string $keyModelClass = 'chriskacerguis\\RestServer\\Models\\KeyModel';

    // Logging
    public bool $enableLogging = false;
    public string $logsTable = 'logs';
    public bool $logsJsonParams = false;

    // Access control
    public bool $enableAccess = false;
    public string $accessTable = 'access';

    // Rate limits
    /** @var 'IP_ADDRESS'|'API_KEY'|'METHOD_NAME'|'ROUTED_URL' */
    public string $limitsMethod = 'ROUTED_URL';
    public bool $enableLimits = false;
    public string $limitsTable = 'limits';
    public int $limitDefaultPerHour = 60; // default requests per hour
    public int $limitWindowSeconds = 3600; // default window length
    /**
     * Override the model class used to persist limits (for testing or customization)
     *
     * @var class-string<\chriskacerguis\RestServer\Models\LimitModel>
     */
    public string $limitModelClass = 'chriskacerguis\\RestServer\\Models\\LimitModel';

    // HTTP Accept
    public bool $ignoreHttpAccept = false;

    // AJAX only
    public bool $ajaxOnly = false;

    // Language (unused in CI4 version but kept for parity)
    public string $language = 'english';

    // CORS
    public bool $checkCors = false;
    /** @var array<string> */
    public array $allowedCorsHeaders = [
        'Origin', 'X-Requested-With', 'Content-Type', 'Accept', 'Access-Control-Request-Method',
    ];
    /** @var array<string> */
    public array $allowedCorsMethods = ['GET', 'POST', 'OPTIONS', 'PUT', 'PATCH', 'DELETE'];
    public bool $allowAnyCorsDomain = false;
    /** @var array<string> */
    public array $allowedCorsOrigins = [];
    /** @var array<string,string> */
    public array $forcedCorsHeaders = [];
}
