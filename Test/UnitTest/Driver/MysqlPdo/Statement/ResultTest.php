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

namespace OS\DatabaseAccessLayer\Test\UnitTest\Driver\MysqlPdo\Statement;


use OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement\Result;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidFetchTypeException;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\Helper\Stub;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;

class ResultTest extends UnitTestCase
{
    private function getPdoStatementStub(): \PDOStatement
    {
        return Stub::make(\PDOStatement::class);
    }

    private function getResultStub(array $properties = [], \PDOStatement $statement = null): Result
    {
        return Stub::make(Result::class, array_merge([
            'statement' => $statement ?? $this->getPdoStatementStub(),
            'pointer' => 0
        ], $properties));
    }

    public function validIteratorTypeDataProvider()
    {
        return [
            'assoc' => [ \OS\DatabaseAccessLayer\Statement\Result::ITERATOR_FETCH_ASSOC ],
            'object' => [ \OS\DatabaseAccessLayer\Statement\Result::ITERATOR_FETCH_OBJECT ]
        ];
    }

    public function invalidIteratorTypeDataProvider()
    {
        return [
            '-1' => [ -1 ],
            '-2' => [ -2 ],
            '1234' => [ 1234 ]
        ];
    }

    /**
     * @param int $fetchType
     *
     * @throws InvalidFetchTypeException
     * @throws \ReflectionException
     *
     * @dataProvider validIteratorTypeDataProvider
     */
    public function testSetIteratorFetchTypeWillAcceptValidTypes(int $fetchType)
    {
        $result = $this->getResultStub();
        $response = $result->setIteratorFetchType($fetchType);

        verify($response)->same($result);
        verify((new ObjectInspector($result))->getValue('iteratorFetchType'))->equals($fetchType);
    }

    /**
     * @param int $fetchType
     *
     * @throws InvalidFetchTypeException
     *
     * @dataProvider invalidIteratorTypeDataProvider
     */
    public function testSetIteratorFetchTypeWillRejectInvalidTypes(int $fetchType)
    {
        $this->expectException(InvalidFetchTypeException::class);

        $result = $this->getResultStub();
        $result->setIteratorFetchType($fetchType);
    }

    /**
     * @param int $fetchType
     *
     * @throws \ReflectionException
     *
     * @dataProvider validIteratorTypeDataProvider
     * @dataProvider invalidIteratorTypeDataProvider
     */
    public function testGetIteratorFetchType(int $fetchType)
    {
        $result = $this->getResultStub();
        (new ObjectInspector($result))->setValue('iteratorFetchType', $fetchType);
        verify($result->getIteratorFetchType())->equals($fetchType);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCurrentWillReturnCachedValue()
    {
        $expectedObject = (object) [ 'foo' => 'bar' ];

        $result = $this->getResultStub();
        (new ObjectInspector($result))->setValue('current', $expectedObject);

        verify($result->current())->equals($expectedObject);
        verify($result->current())->equals($expectedObject);
        verify($result->current())->equals($expectedObject);
    }

    public function testFetchFieldWillReturnCachedValueWithAssocArray()
    {
        $row = [ 'foo' => 'bar', 'baz' => null ];
        $result = $this->getResultStub([
            'current' => $row,
            'iteratorFetchType' => Result::ITERATOR_FETCH_ASSOC
        ]);

        verify($result->fetchField('foo'))->equals('bar');
        verify($result->fetchField('baz'))->equals(null);
    }

    public function testFetchFieldWillReturnCachedValueWithObject()
    {
        $row = (object) [ 'foo' => 'bar', 'baz' => null ];
        $result = $this->getResultStub([
            'current' => $row,
            'iteratorFetchType' => Result::ITERATOR_FETCH_OBJECT
        ]);

        verify($result->fetchField('foo'))->equals('bar');
        verify($result->fetchField('baz'))->equals(null);
    }

    public function testFetchRow()
    {
        $row = (object) [ 'foo' => 'bar', 'baz' => null ];
        $result = $this->getResultStub([ 'current' => $row ]);

        verify($result->fetchRow())->equals($row);
        verify($result->fetchRow())->equals($row);
    }

    /**
     * @throws \ReflectionException
     */
    public function testNext()
    {
        $rows = [
            (object) [ 'foo' => 'bar', 'baz' => null ],
            (object) [ 'foo' => 'baz', 'baz' => true ],
            (object) [ 'foo' => 'foo', 'baz' => false ]
        ];

        $pdoStatement = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalClone()
            ->setMethods([ 'fetch' ])
            ->getMock();

        $pdoStatement->expects($this::exactly(3))
            ->method('fetch')
            ->with(
                \PDO::FETCH_OBJ,
                \PDO::FETCH_ORI_NEXT,
                null
            )
            ->willReturnOnConsecutiveCalls(
                $rows[0],
                $rows[1],
                $rows[2]
            );

        $result = $this->getResultStub([], $pdoStatement);

        $result->next();
        verify($result->current())->equals($rows[0]);

        $result->next();
        verify($result->current())->equals($rows[1]);

        $result->next();
        verify($result->current())->equals($rows[2]);

        verify((new ObjectInspector($result))->getValue('pointer'))->equals(3);
    }

    /**
     * @throws \ReflectionException
     */
    public function testPrevious()
    {
        $rows = [
            (object) [ 'foo' => 'bar', 'baz' => null ],
            (object) [ 'foo' => 'baz', 'baz' => true ],
            (object) [ 'foo' => 'foo', 'baz' => false ]
        ];

        $pdoStatement = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalClone()
            ->setMethods([ 'fetch' ])
            ->getMock();

        $pdoStatement->expects($this::exactly(3))
            ->method('fetch')
            ->with(
                \PDO::FETCH_OBJ,
                \PDO::FETCH_ORI_PRIOR,
                null
            )
            ->willReturnOnConsecutiveCalls(
                $rows[0],
                $rows[1],
                $rows[2]
            );

        $result = $this->getResultStub([ 'pointer' => 3 ], $pdoStatement);

        $result->previous();
        verify($result->current())->equals($rows[0]);

        $result->previous();
        verify($result->current())->equals($rows[1]);

        $result->previous();
        verify($result->current())->equals($rows[2]);

        verify((new ObjectInspector($result))->getValue('pointer'))->equals(0);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSeek()
    {
        $rows = [
            [ 'foo' => 'bar', 'baz' => null ],
            [ 'foo' => 'baz', 'baz' => true ],
            [ 'foo' => 'foo', 'baz' => false ]
        ];

        $pdoStatement = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalClone()
            ->setMethods([ 'fetch' ])
            ->getMock();

        $pdoStatement->expects($this::exactly(3))
            ->method('fetch')
            ->withConsecutive(
                [ \PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, 3 ],
                [ \PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, 0 ],
                [ \PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, 20 ]
            )
            ->willReturnOnConsecutiveCalls(
                $rows[0],
                $rows[1],
                $rows[2]
            );

        $result = $this->getResultStub([
            'pointer' => 0,
            'iteratorFetchType' => Result::ITERATOR_FETCH_ASSOC
        ], $pdoStatement);

        $result->seek(3);
        verify($result->current())->equals($rows[0]);

        $result->seek(0);
        verify($result->current())->equals($rows[1]);

        $result->seek(20);
        verify($result->current())->equals($rows[2]);

        verify((new ObjectInspector($result))->getValue('pointer'))->equals(20);
    }

    public function testKey()
    {
        $result = $this->getResultStub([ 'pointer' => 33 ]);
        verify($result->key())->equals(33);
    }

    /**
     * @throws \ReflectionException
     */
    public function testValid()
    {
        $pdoStatement = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalClone()
            ->setMethods([ 'rowCount' ])
            ->getMock();

        $pdoStatement->expects($this::exactly(3))
            ->method( 'rowCount' )
            ->willReturn(10);

        $result = $this->getResultStub([], $pdoStatement);
        $inspector = new ObjectInspector($result);

        $inspector->setValue('pointer', 0);
        verify($result->valid())->equals(true);

        $inspector->setValue('pointer', 9);
        verify($result->valid())->equals(true);

        $inspector->setValue('pointer', 10);
        verify($result->valid())->equals(false);

        $inspector->setValue('pointer', -1);
        verify($result->valid())->equals(false);
    }

    public function testRewind()
    {
        $row = (object) [ 'foo' => 'bar', 'baz' => null ];
        $pdoStatement = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalClone()
            ->setMethods([ 'fetch' ])
            ->getMock();

        $pdoStatement->expects($this::exactly(1))
            ->method('fetch')
            ->with(\PDO::FETCH_OBJ, \PDO::FETCH_ORI_ABS, 0)
            ->willReturn($row);

        $result = $this->getResultStub([ 'pointer' => 3 ], $pdoStatement);

        $result->rewind();
        $result->rewind();
        $result->rewind();
    }
}
