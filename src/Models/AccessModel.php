<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Models;

use CodeIgniter\Model;
use chriskacerguis\RestServer\Config\Rest as RestConfig;

class AccessModel extends Model
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
        'key', 'controller', 'class', 'method', 'created_at',
    ];
    /** @var bool */
    protected $useTimestamps = false;

    public function __construct(mixed $db = null, ?RestConfig $config = null)
    {
        $config = $config ?? new RestConfig();
        $this->table = $config->accessTable;
        parent::__construct($db);
    }
}
