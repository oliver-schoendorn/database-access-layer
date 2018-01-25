<?php

namespace OS\DatabaseAccessLayer\Test\Helper\Fixture;


use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\Database\Table;
use PHPUnit\DbUnit\DataSet\ITable;
use PHPUnit\DbUnit\DataSet\ITableMetadata;

abstract class TableFixture
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ITable
     */
    private $table;

    /**
     * TableFixture constructor.
     *
     * @param Connection $databaseConnection
     */
    public function __construct(Connection $databaseConnection)
    {
        $this->initializeTable($databaseConnection->getConnection());

        $this->connection = $databaseConnection;
        $this->table = new Table($this->getTableMeta(), $this->connection);
    }

    private function initializeTable(\PDO $pdo)
    {
        $tableName = $pdo->quote($this->getTableName());
        $statement = $pdo->query('SHOW TABLES LIKE ' . $tableName . ';');
        $statement->execute();

        if ($statement->rowCount() === 0) {
            $this->createTable($pdo);
        }
    }

    /**
     * Must return the table name
     *
     * @return string
     */
    abstract public function getTableName(): string;

    /**
     * Must create the table
     *
     * This method is only called if the table does not exist
     *
     * @param \PDO $pdo
     *
     * @return void
     */
    abstract protected function createTable(\PDO $pdo);

    /**
     * @return ITableMetadata
     */
    abstract protected function getTableMeta(): ITableMetadata;

    /**
     * @return ITable
     */
    public function getTable(): ITable
    {
        return $this->table;
    }

    /**
     * @return int
     */
    public function getRowCount(): int
    {
        return $this->connection->getRowCount($this->getTableName()) ?? 0;
    }
}
