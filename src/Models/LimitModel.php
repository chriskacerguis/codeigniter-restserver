<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Models;

use CodeIgniter\Model;
use chriskacerguis\RestServer\Config\Rest as RestConfig;

class LimitModel extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'uri', 'class', 'method', 'api_key', 'ip_address', 'count', 'limit', 'reset_at', 'created_at', 'updated_at',
    ];
    protected $useTimestamps = false;

    public function __construct($db = null, ?RestConfig $config = null)
    {
        $config = $config ?? new RestConfig();
        $this->table = $config->limitsTable;
        parent::__construct($db);
    }
}
