<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Models;

use chriskacerguis\RestServer\Config\Rest as RestConfig;
use CodeIgniter\Model;

class KeyModel extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'key', 'level', 'ignore_limits', 'is_private_key', 'ip_addresses', 'created_at',
    ];
    protected $useTimestamps = false;

    public function __construct($db = null, ?RestConfig $config = null)
    {
        $config = $config ?? new RestConfig();
        $this->table = $config->keysTable;
        parent::__construct($db);
    }

    public function findValidKey(string $key): ?array
    {
        $config = new RestConfig();

        $builder = $this->builder();
        $builder->where('`key`', $key);

        if ($config->keysExpire && $config->keysExpiryColumn) {
            $builder->groupStart()
                ->where($config->keysExpiryColumn, null)
                ->orWhere($config->keysExpiryColumn.' >', gmdate('Y-m-d H:i:s'))
                ->groupEnd();
        }

        $row = $builder->get()->getRowArray();

        return $row ?: null;
    }
}
