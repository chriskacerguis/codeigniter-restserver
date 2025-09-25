<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Models;

use CodeIgniter\Model;
use chriskacerguis\RestServer\Config\Rest as RestConfig;

class LogModel extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'key', 'uri', 'method', 'params', 'api_version', 'ip_address', 'authorized', 'response_code', 'created_at',
    ];
    protected $useTimestamps = false;

    public function __construct($db = null, ?RestConfig $config = null)
    {
        $config = $config ?? new RestConfig();
        $this->table = $config->logsTable;
        parent::__construct($db);
    }
}
