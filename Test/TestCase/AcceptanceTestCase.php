<?php

namespace OS\DatabaseAccessLayer\Test\TestCase;


use OS\DatabaseAccessLayer\Config\DatabaseConfig;
use OS\DatabaseAccessLayer\Driver;
use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\TestCase;

abstract class AcceptanceTestCase extends TestCase
{
    /**
     * @var Connection|null
     */
    private $connection;

    /**
     * @var Driver|null
     */
    private $driver;

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->init();
    }

    protected function init() {}

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        if ( ! $this->connection) {
            $dsn = 'mysql:' .
                   'host=' . $_ENV['DB_HOST'] . ';' .
                   'dbname=' . $_ENV['DB_NAME'] . ';' .
                   'charset=utf8mb4';

            $pdo = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $this->connection = $this->createDefaultDBConnection($pdo, $_ENV['DB_NAME']);
        }

        return $this->connection;
    }

    protected function getDriverConfig(): DatabaseConfig
    {
        return new DatabaseConfig([
            'host' => $_ENV['DB_HOST'],
            'name' => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASS']
        ]);
    }

    /**
     * @return Driver
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     */
    protected function getDriver(): Driver
    {
        if ( ! $this->driver) {
            $config = $this->getDriverConfig();
            $this->driver = new Driver\MysqlPdo\Driver($config);
        }

        return $this->driver;
    }
}
