<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Models;

use CodeIgniter\Model;
use chriskacerguis\RestServer\Config\Rest as RestConfig;

class LogModel extends Model
{
    /** @var string */
    protected $table;
    /** @var string */
    protected $primaryKey = 'id';
    /** @var string */
    protected $returnType = 'array';
    /** @var bool */
    protected $useSoftDeletes = false;
    /** @var array<string> */
    protected $allowedFields = [
        'key', 'uri', 'method', 'params', 'api_version', 'ip_address', 'authorized', 'response_code', 'created_at',
    ];
    /** @var bool */
    protected $useTimestamps = false;

    public function __construct(mixed $db = null, ?RestConfig $config = null)
    {
        $config = $config ?? new RestConfig();
        $this->table = $config->logsTable;
        parent::__construct($db);
    }
}
