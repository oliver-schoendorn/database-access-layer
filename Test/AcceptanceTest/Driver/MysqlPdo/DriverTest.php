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

namespace OS\DatabaseAccessLayer\Test\AcceptanceTest\Driver\MysqlPdo;


use OS\DatabaseAccessLayer\Config\DatabaseConfig;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Driver;
use OS\DatabaseAccessLayer\Exception\AuthenticationException;
use OS\DatabaseAccessLayer\Exception\UnknownDatabaseException;
use OS\DatabaseAccessLayer\Exception\UnreachableException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;
use OS\DatabaseAccessLayer\Statement\Result;
use OS\DatabaseAccessLayer\Test\Helper\Fixture\UserTable;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\TestCase\AcceptanceTestCase;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use PHPUnit\DbUnit\DataSet\IDataSet;

class DriverTest extends AcceptanceTestCase
{
    /**
     * @var UserTable
     */
    private $table;

    /**
     * Sets up all tables necessary for this test case
     */
    protected function init()
    {
        $this->table = new UserTable($this->getConnection());
    }

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet(): IDataSet
    {
        return new ArrayDataSet([ $this->table->getTableName() => [
            [
                'id' => 1,
                'name' => 'Foo Bar',
                'password' => hex2bin(md5('lol')),
                'created' => '2018-01-25 08:00:00',
                'nullable' => 3,
                'bool' => 1
            ],
            [
                'id' => 2,
                'name' => 'Lorem ipsum',
                'password' => hex2bin(md5('baz')),
                'created' => '2018-01-25 07:30:00',
                'nullable' => null,
                'bool' => 0
            ]
        ]]);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \ReflectionException
     */
    public function testConstructorWillConnectToDatabase()
    {
        $driver = $this->getDriver();
        $inspector = new ObjectInspector($driver);

        $pdo = $inspector->getValue('pdo');
        verify($pdo)->isInstanceOf(\PDO::class);
        verify($pdo->errorCode())->equals(0);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     */
    public function testConstructorWillThrowExceptionOnInvalidHost()
    {
        $this->expectException(UnreachableException::class);

        $config = new DatabaseConfig([ 'host' => '1.1.1.1' ]);
        new Driver($config);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \ReflectionException
     */
    public function testConstructorWillThrowExceptionOnInvalidCredentials()
    {
        $this->expectException(AuthenticationException::class);

        $config = $this->getDriverConfig();
        (new ObjectInspector($config))->setValue('password', 'fooBar');
        new Driver($config);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \ReflectionException
     */
    public function testConstructorWillThrowExceptionOnInvalidDatabase()
    {
        $this->expectException(UnknownDatabaseException::class);

        $config = $this->getDriverConfig();
        (new ObjectInspector($config))->setValue('name', 'fooBar');
        new Driver($config);
    }

    public function escapeDataProvider()
    {
        return [
            'valid string' => [ 'foo Bar', '\'foo Bar\''],
            'quoted string' => [ 'foo \' Bar', '\'foo \\\' Bar\''],
            'double quoted' => [ 'foo "bar', '\'foo \"bar\'']
        ];
    }

    /**
     * @param string $input
     * @param string $expected
     *
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     *
     * @dataProvider escapeDataProvider
     */
    public function testEscape(string $input, string $expected)
    {
        $driver = $this->getDriver();
        verify($driver->escape($input))->equals($expected);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException
     */
    public function testGetLastInsertedId()
    {
        $driver = $this->getDriver();
        $driver->query('
                INSERT INTO `' . $this->table->getTableName() . '` SET
                    `name` = "testGetLastInsertedId",
                    `password` = "' . hex2bin(md5('Foo')) . '",
                    `created` = NOW(),
                    `nullable` = NULL,
                    `bool` = FALSE;
            ');

        $insertedId = $driver->getLastInsertedId();
        verify($insertedId)->internalType('int');
        verify($insertedId)->notNull();

        $result = $driver->query('SELECT max(id) AS id FROM ' . $this->table->getTableName());
        verify($result->fetchField('id'))->equals($insertedId);
    }

    public function testTransaction()
    {
        $driver = $this->getDriver();
        $table = $this->table;
        $initialRowCount = $table->getRowCount();

        try {
            verify($driver->transactionStart())->true();
            $driver->query('
                INSERT INTO `' . $this->table->getTableName() . '` SET
                    `name` = "testTransaction",
                    `password` = "' . hex2bin(md5('Foo')) . '",
                    `created` = NOW(),
                    `nullable` = NULL,
                    `bool` = FALSE;
            ');

            verify($driver->transactionCommit())->true();
            verify($table->getRowCount())->equals($initialRowCount + 1);

            verify($table->getRowCount())->equals($initialRowCount + 1);
            verify($driver->transactionStart())->true();

            $driver->query('DELETE FROM `'. $this->table->getTableName() .'` WHERE `id` = ' . $driver->getLastInsertedId());
            verify($driver->transactionAbort())->true();

            verify($table->getRowCount())->equals($initialRowCount + 1);
        }
        finally {
            try {
                $driver->transactionAbort();
            }
            catch (\PDOException $e) {}
        }
    }

    public function testQueryWillReturnResult()
    {
        $driver = $this->getDriver();
        $result = $driver->query('SELECT * FROM `' . $this->table->getTableName() . '` LIMIT 1;');

        verify($result)->isInstanceOf(Result::class);
        verify($result->count())->equals(1);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \ReflectionException
     */
    public function testQueryExceptionWillNotLeakData()
    {
        $driverConfig = $this->getDriverConfig();
        (new ObjectInspector($driverConfig))->setValue('debug', false);
        $driver = new Driver($driverConfig);

        try {
            $driver->query('SELECT * FROM UNKNOWN_TABLE;');
            throw new \Exception('Test failed, expected exception.');
        }
        catch (\Throwable $exception) {
            verify($exception)->isInstanceOf(StatementExecutionException::class);
            verify($exception->getCode())->equals(1011);
            verify($exception->getMessage())->notContains('UNKNWON_TABLE');
        }
    }

    /**
     * @throws StatementExecutionException
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \ReflectionException
     */
    public function testQueryExceptionInDebugMode()
    {
        $this->expectException(StatementExecutionException::class);

        $driverConfig = $this->getDriverConfig();
        (new ObjectInspector($driverConfig))->setValue('debug', true);
        $driver = new Driver($driverConfig);
        $driver->query('SELECT * FROM UNKNOWN_TABLE;');
    }
}
