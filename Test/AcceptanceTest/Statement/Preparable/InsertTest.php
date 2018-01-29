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

namespace OS\DatabaseAccessLayer\Test\AcceptanceTest\Statement\Preparable;


use OS\DatabaseAccessLayer\Expression\Reference\FieldReference;
use OS\DatabaseAccessLayer\Expression\Reference\TableReference;
use OS\DatabaseAccessLayer\Statement\Preparable\Insert;
use OS\DatabaseAccessLayer\Test\Helper\Fixture\UserTable;
use OS\DatabaseAccessLayer\Test\TestCase\AcceptanceTestCase;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use PHPUnit\DbUnit\DataSet\IDataSet;

class InsertTest extends AcceptanceTestCase
{
    /**
     * @var UserTable
     */
    private $userTable;

    /**
     * @var array[]
     */
    private $initialDataSet;

    private $exampleRows;

    /**
     * Initializes the user table fixture
     */
    protected function init()
    {
        $this->userTable = new UserTable($this->getConnection());
        $this->initialDataSet = [[
            'id' => 1,
            'name' => 'Foo Bar',
            'password' => hex2bin(md5('foo')),
            'created' => '2018-01-27 15:10:00',
            'nullable' => null,
            'bool' => 0
        ]];

        $this->exampleRows = [
            [
                'id' => null,
                'name' => 'Foo Bar 2',
                'password' => hex2bin(md5('foo2')),
                'created' => '2018-01-27 18:00:00',
                'nullable' => null,
                'bool' => true
            ],
            [
                'id' => null,
                'name' => 'Foo Bar 3',
                'password' => hex2bin(md5('foo3')),
                'created' => '2018-01-27 18:00:00',
                'nullable' => 12,
                'bool' => false
            ]
        ];
    }

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        return new ArrayDataSet([ $this->userTable->getTableName() => $this->initialDataSet ]);
    }

    private function getUserTableReference(): TableReference
    {
        return new TableReference($this->userTable->getTableName());
    }

    private function getUserTableColumns(): array
    {
        return [
            new FieldReference('id'),
            new FieldReference('name'),
            new FieldReference('password'),
            new FieldReference('created'),
            new FieldReference('nullable'),
            new FieldReference('bool')
        ];
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingColumnException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException
     * @throws \Throwable
     */
    public function testInsertSingleRow()
    {
        $newRow = $this->exampleRows[0];
        $driver = $this->getDriver();

        $insert = (new Insert($this->getUserTableReference(), $this->getUserTableColumns()))
            ->addRow($newRow);

        verify($insert->execute($driver))->equals([ 2 ]);

        // Fix values
        $newRow['id'] = '2';
        $newRow['bool'] = '1';

        $rows = $this->userTable->fetchAll(\PDO::FETCH_ASSOC);
        verify($rows)->equals([
            $this->initialDataSet[0],
            $newRow
        ]);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingColumnException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException
     * @throws \Throwable
     */
    public function testInsertRowWithoutKeys()
    {
        $newRow = $this->exampleRows[0];
        $driver = $this->getDriver();

        $insert = (new Insert($this->getUserTableReference(), $this->getUserTableColumns()))
            ->addRow(array_values($newRow));

        verify($insert->execute($driver))->equals([ 2 ]);

        // Fix values
        $newRow['id'] = '2';
        $newRow['bool'] = '1';

        $rows = $this->userTable->fetchAll(\PDO::FETCH_ASSOC);
        verify($rows)->equals([
            $this->initialDataSet[0],
            $newRow
        ]);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingColumnException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException
     * @throws \Throwable
     */
    public function testInsertMultipleRows()
    {
        $newValues = $this->exampleRows;
        $driver = $this->getDriver();

        $insert = (new Insert($this->getUserTableReference(), $this->getUserTableColumns()))
            ->addRow($newValues[0])
            ->addRow($newValues[1]);

        $response = $insert->execute($driver);
        verify($response)->equals([ 2, 3 ]);

        // Fix values
        $newValues[0]['id'] = '2';
        $newValues[0]['bool'] = '1';

        $newValues[1]['id'] = '3';
        $newValues[1]['bool'] = '0';

        $rows = $this->userTable->fetchAll(\PDO::FETCH_ASSOC);
        verify($rows)->equals([
            $this->initialDataSet[0],
            $newValues[0],
            $newValues[1]
        ]);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingColumnException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException
     * @throws \Throwable
     */
    public function testInsertPreservesColumnOrderPrepared()
    {
        $newValues = $this->exampleRows;
        $driver = $this->getDriver();

        $insert = (new Insert($this->getUserTableReference(), $this->getUserTableColumns()))
            ->addRow($newValues[0])
            ->addRow([
                'name' => $newValues[1]['name'],
                'bool' => $newValues[1]['bool'],
                'created' => $newValues[1]['created'],
                'nullable' => $newValues[1]['nullable'],
                'id' => $newValues[1]['id'],
                'password' => $newValues[1]['password']
            ]);

        // Fix values
        $newValues[0]['id'] = '2';
        $newValues[0]['bool'] = '1';

        $newValues[1]['id'] = '3';
        $newValues[1]['bool'] = '0';

        $response = $insert->execute($driver);
        verify($response)->equals([ 2, 3 ]);

        $rows = $this->userTable->fetchAll(\PDO::FETCH_ASSOC);
        verify($rows)->equals([
            $this->initialDataSet[0],
            $newValues[0],
            $newValues[1]
        ]);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Exception\DriverException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidColumnKeyException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidFetchTypeException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingColumnException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException
     */
    public function testInsertPreservesColumnOrderQuery()
    {
        $newValues = $this->exampleRows;
        $driver = $this->getDriver();

        $insert = (new Insert($this->getUserTableReference(), $this->getUserTableColumns()))
            ->addRow($newValues[0])
            ->addRow([
                'name' => $newValues[1]['name'],
                'bool' => $newValues[1]['bool'],
                'created' => $newValues[1]['created'],
                'nullable' => $newValues[1]['nullable'],
                'id' => $newValues[1]['id'],
                'password' => $newValues[1]['password']
            ]);

        // Fix values
        $newValues[0]['id'] = '2';
        $newValues[0]['bool'] = '1';

        $newValues[1]['id'] = '3';
        $newValues[1]['bool'] = '0';

        $query = $insert->toSql($driver->getSpecification());
        $result = $driver->query($query);
        verify($result->count())->equals(2);

        $rows = $this->userTable->fetchAll(\PDO::FETCH_ASSOC);
        verify($rows)->equals([
            $this->initialDataSet[0],
            $newValues[0],
            $newValues[1]
        ]);
    }
}
