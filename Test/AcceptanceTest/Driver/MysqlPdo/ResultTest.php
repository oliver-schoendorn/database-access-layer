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


use OS\DatabaseAccessLayer\Statement\Result;
use OS\DatabaseAccessLayer\Test\Helper\Fixture\UserTable;
use OS\DatabaseAccessLayer\Test\TestCase\AcceptanceTestCase;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use PHPUnit\DbUnit\DataSet\IDataSet;

class ResultTest extends AcceptanceTestCase
{
    /**
     * @var UserTable
     */
    private $userTable;

    /**
     * @var array[]
     */
    private $dataSet;

    /**
     * Initialize user table fixture
     */
    protected function init()
    {
        $this->dataSet = [
            [ 'id' => 1, 'name' => 'User 1', 'password' => hex2bin(md5('test1')), 'created' => '2018-01-27 00:00:00', 'nullable' => 1, 'bool' => 0 ],
            [ 'id' => 2, 'name' => 'User 2', 'password' => hex2bin(md5('test2')), 'created' => '2018-01-26 00:00:00', 'nullable' => null, 'bool' => 0 ],
            [ 'id' => 3, 'name' => 'User 3', 'password' => hex2bin(md5('test3')), 'created' => '2018-01-25 00:00:00', 'nullable' => 1, 'bool' => 1 ],
            [ 'id' => 4, 'name' => 'User 4', 'password' => hex2bin(md5('test4')), 'created' => '2018-01-24 00:00:00', 'nullable' => 1, 'bool' => 0 ],
            [ 'id' => 5, 'name' => 'User 5', 'password' => hex2bin(md5('test5')), 'created' => '2018-01-23 00:00:00', 'nullable' => null, 'bool' => 1 ],
        ];
        $this->userTable = new UserTable($this->getConnection());
    }

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        return new ArrayDataSet([ $this->userTable->getTableName() => $this->dataSet]);
    }

    /**
     * @param string $query
     *
     * @return Result
     * @throws \Exception
     */
    private function makeResult(string $query): Result
    {
        return $this->getDriver()->query($query)->setIteratorFetchType(Result::ITERATOR_FETCH_ASSOC);
    }

    /**
     * @throws \Exception
     */
    public function testSelectAll()
    {
        $result = $this->makeResult('SELECT * FROM `' . $this->userTable->getTableName() . '` ORDER BY `id` ASC;');
        verify($result->count())->equals(5);
        verify($result->current())->equals($this->dataSet[0]);
    }

    /**
     * @throws \Exception
     */
    public function testIterator()
    {
        $result = $this->makeResult('SELECT * FROM `' . $this->userTable->getTableName() . '` ORDER BY `id` ASC;');
        verify($result->count())->equals(5);

        $allResults = [];
        foreach ($result as $row) {
            array_push($allResults, $row);
        }

        verify(count($allResults))->equals(5);
        verify($allResults)->equals($this->dataSet);
    }

    /**
     * @throws \Exception
     */
    public function testRewind()
    {
        $result = $this->makeResult('SELECT * FROM `' . $this->userTable->getTableName() . '` ORDER BY `id` ASC;');
        verify($result->count())->equals(5);

        $allResults = [];
        foreach ($result as $row) {
            array_push($allResults, $row);
        }

        $result->rewind();
        foreach ($result as $row) {
            array_push($allResults, $row);
        }

        verify(count($allResults))->equals(10);
        verify($allResults)->equals(array_merge($this->dataSet, $this->dataSet));
    }

    /**
     * @throws \Exception
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidFetchTypeException
     */
    public function testFetchField()
    {
        $result = $this->makeResult('SELECT * FROM `' . $this->userTable->getTableName() . '` WHERE `id` = 1;');
        verify($result->count())->equals(1);

        verify($result->fetchField('id'))->equals(1);
        verify($result->fetchField('name'))->equals($this->dataSet[0]['name']);

        verify($result->getIteratorFetchType())->equals($result::ITERATOR_FETCH_ASSOC);
        $result->setIteratorFetchType($result::ITERATOR_FETCH_OBJECT);

        verify($result->fetchField('id'))->equals(1);
        verify($result->fetchField('name'))->equals($this->dataSet[0]['name']);

        verify($result->getIteratorFetchType())->equals($result::ITERATOR_FETCH_OBJECT);
    }

    /**
     * @throws \Exception
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidFetchTypeException
     */
    public function testFetchRow()
    {
        $result = $this->makeResult('SELECT * FROM `' . $this->userTable->getTableName() . '` WHERE `id` = 1;');
        verify($result->count())->equals(1);

        verify($result->fetchRow())->equals($this->dataSet[0]);
        $result->setIteratorFetchType($result::ITERATOR_FETCH_OBJECT);

        verify($result->fetchRow())->equals((object) $this->dataSet[0]);
    }
}
