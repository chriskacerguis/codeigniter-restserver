<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Models;

use CodeIgniter\Model;
use chriskacerguis\RestServer\Config\Rest as RestConfig;

class KeyModel extends Model
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
        'key', 'level', 'ignore_limits', 'is_private_key', 'ip_addresses', 'created_at',
    ];
    /** @var bool */
    protected $useTimestamps = false;

    public function __construct(mixed $db = null, ?RestConfig $config = null)
    {
        $config = $config ?? new RestConfig();
        $this->table = $config->keysTable;
        parent::__construct($db);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findValidKey(string $key): ?array
    {
        $config = new RestConfig();

        /** @var \CodeIgniter\Database\BaseBuilder $builder */
        $builder = $this->builder();
        $builder->where('`key`', $key);

        if ($config->keysExpire && $config->keysExpiryColumn) {
            $builder->groupStart()
                ->where($config->keysExpiryColumn, null)
                ->orWhere($config->keysExpiryColumn . ' >', gmdate('Y-m-d H:i:s'))
                ->groupEnd();
        }

        /** @var array<string,mixed>|null $row */
        $row = $builder->get()->getRowArray();
        return is_array($row) ? $row : null;
    }
}
