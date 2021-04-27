<?php

namespace Core\DB;

use \PDO;

abstract class ConnectionBase
{
    /**
     * @var PDO
     */
    protected $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(getenv('DB_PDO_DSN'), getenv('DB_PDO_USER'), getenv('DB_PDO_PASSWORD'));
    }
}