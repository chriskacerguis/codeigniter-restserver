<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Models;

use CodeIgniter\Model;
use chriskacerguis\RestServer\Config\Rest as RestConfig;

class LimitModel extends Model
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
        'uri', 'class', 'method', 'api_key', 'ip_address', 'count', 'limit', 'reset_at', 'created_at', 'updated_at',
    ];
    /** @var bool */
    protected $useTimestamps = false;

    public function __construct(mixed $db = null, ?RestConfig $config = null)
    {
        $config = $config ?? new RestConfig();
        $this->table = $config->limitsTable;
        parent::__construct($db);
    }
}
