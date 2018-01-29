<?php
/**
 * Copyright (c) 2018 Oliver Schöndorn
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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

    public function fetchAll(int $fetchType = \PDO::FETCH_ASSOC): array
    {
        $statement = $this->connection->getConnection()->prepare(
            'SELECT * FROM `' . $this->getTableName() . '` ORDER BY `id` ASC;');

        $statement->execute();
        return $statement->fetchAll($fetchType);
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
