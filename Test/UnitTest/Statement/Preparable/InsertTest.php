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

namespace OS\DatabaseAccessLayer\Test\UnitTest\Statement\Preparable;


use OS\DatabaseAccessLayer\Driver;
use OS\DatabaseAccessLayer\Expression\Reference\FieldReference;
use OS\DatabaseAccessLayer\Expression\Reference\TableReference;
use OS\DatabaseAccessLayer\Specification;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidColumnKeyException;
use OS\DatabaseAccessLayer\Statement\Exception\MissingColumnException;
use OS\DatabaseAccessLayer\Statement\ParameterContainer;
use OS\DatabaseAccessLayer\Statement\ParameterContainerInterface;
use OS\DatabaseAccessLayer\Statement\Preparable\Insert;
use OS\DatabaseAccessLayer\Statement\PreparedStatement;
use OS\DatabaseAccessLayer\Statement\Result;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;

class InsertTest extends UnitTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testConstructorWillStoreTableReference()
    {
        $table = new TableReference('test');
        $insert = new Insert($table);
        verify((new ObjectInspector($insert))->getValue('table'))->same($table);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructorWillNormalizeColumns()
    {
        $insert = new Insert(new TableReference('test'), [
            new FieldReference('id', 'user_id'),
            new FieldReference('name', 'user_name'),
            'created'
        ]);

        $columns = (new ObjectInspector($insert))->getValue('columns');
        verify($columns)->internalType('array');
        foreach ($columns as $column) {
            verify($column)->internalType('string');
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetColumnsWillNormalizeColumns()
    {
        $columns = [
            new FieldReference('id', 'user_id'),
            new FieldReference('name', 'user_name'),
            'created'
        ];
        $insert = new Insert(new TableReference('test'));
        verify($insert->setColumns($columns))->same($insert);

        $columns = (new ObjectInspector($insert))->getValue('columns');
        verify($columns)->same($insert->getColumns());
        verify($columns)->internalType('array');
        foreach ($columns as $column) {
            verify($column)->internalType('string');
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testAddRowAndGetRows()
    {
        $exampleRow = [
            'foo' => 'bar',
            'baz' => 'ipsum'
        ];

        $insert = new Insert(new TableReference('test'));
        $insert->addRow($exampleRow);
        $insert->addRow($exampleRow);
        $insert->addRow($exampleRow);

        verify($insert->getRows())->same((new ObjectInspector($insert))->getValue('values'));
        verify($insert->getRows())->equals([
            $exampleRow,
            $exampleRow,
            $exampleRow
        ]);
    }

    /**
     * @throws InvalidColumnKeyException
     * @throws MissingColumnException
     */
    public function testToSql()
    {
        $exampleRows = [
            [ null, 'foo bar', null, true ],
            [ null, 'baz ipsum', 12, false ],
            [ 'id' => null, 'name' => 'name', 'nullable' => null, 'bool' => true ],
            [ 'name' => 'name 2', 'nullable' => 2, 'bool' => true ],
            [ 'name' => 'name 3', 'bool' => true, 'id' => null ],
        ];

        $insert = new Insert(new TableReference('user'), [
            'id', 'name', 'nullable', 'bool'
        ]);

        foreach ($exampleRows as $exampleRow) {
            $insert->addRow($exampleRow);
        }

        $specificationMock = $this->getMockBuilder(Specification::class)
            ->setMethods([ 'quoteIdentifier', 'quoteValue' ])
            ->getMockForAbstractClass();

        $specificationMock->expects($this::exactly(5))
            ->method('quoteIdentifier')
            ->withConsecutive(
                [ 'id' ],
                [ 'name' ],
                [ 'nullable' ],
                [ 'bool' ],
                [ 'user' ]
            )
            ->willReturnCallback(function($id) {
                return '`' . $id . '`';
            });

        $specificationMock->expects($this::exactly(20))
            ->method('quoteValue')
            ->withConsecutive(
                [ null ], [ 'foo bar' ], [ null ], [ true ],
                [ null ], [ 'baz ipsum' ], [ 12 ], [ false ],
                [ null ], [ 'name' ], [ null ], [ true ],
                [ null ], [ 'name 2' ], [ 2 ], [ true ],
                [ null ], [ 'name 3' ], [ null ], [ true ]
            )
            ->willReturnOnConsecutiveCalls(
                'NULL', '\'foo bar\'', 'NULL', 'TRUE',
                'NULL', '\'baz ipsum\'' , '\'12\'', 'FALSE',
                'NULL', '\'name\'' , 'NULL', 'TRUE',
                'NULL', '\'name 2\'' , '\'2\'', 'TRUE',
                'NULL', '\'name 3\'' , 'NULL', 'TRUE'
            );

        $query = $insert->toSql($specificationMock);

        verify($query)->startsWith('INSERT INTO `user` (`id`, `name`, `nullable`, `bool`) VALUES');
        verify($query)->contains('(NULL, \'foo bar\', NULL, TRUE)');
        verify($query)->contains('(NULL, \'baz ipsum\', \'12\', FALSE)');
        verify($query)->contains('(NULL, \'name\', NULL, TRUE)');
        verify($query)->contains('(NULL, \'name 2\', \'2\', TRUE)');
        verify($query)->contains('(NULL, \'name 3\', NULL, TRUE)');
    }

    /**
     * @throws InvalidColumnKeyException
     * @throws MissingColumnException
     */
    public function testToSqlWillThrowInvalidColumnKeyException()
    {
        $this->expectException(InvalidColumnKeyException::class);

        $specificationMock = $this->getMockBuilder(Specification::class)
            ->getMockForAbstractClass();

        $insert = new Insert(new TableReference('user'), [ 'id' ]);
        $insert->addRow([ 'bar' => 'test' ]);
        $insert->toSql($specificationMock);
    }

    /**
     * @throws InvalidColumnKeyException
     * @throws MissingColumnException
     */
    public function testToSqlWillThrowInvalidColumnKeyOutOfBoundsException()
    {
        $this->expectException(InvalidColumnKeyException::class);

        $specificationMock = $this->getMockBuilder(Specification::class)
            ->getMockForAbstractClass();

        $insert = new Insert(new TableReference('user'), [ 'id' ]);
        $insert->addRow([ null, 'test' ]);
        $insert->toSql($specificationMock);
    }

    /**
     * @throws InvalidColumnKeyException
     * @throws MissingColumnException
     */
    public function testToSqlWillThrowMissingColumnException()
    {
        $this->expectException(MissingColumnException::class);

        $specificationMock = $this->getMockBuilder(Specification::class)
            ->getMockForAbstractClass();

        $insert = new Insert(new TableReference('user'));
        $insert->addRow([ null, 'test' ]);
        $insert->toSql($specificationMock);
    }

    /**
     * @throws MissingColumnException
     * @throws \ReflectionException
     */
    public function testToPreparableSql()
    {
        $specificationMock = $this->getMockBuilder(Specification::class)
            ->setMethods([ 'quoteIdentifier' ])
            ->getMockForAbstractClass();

        $specificationMock->expects($this::exactly(5))
            ->method('quoteIdentifier')
            ->withConsecutive(
                [ 'id' ],
                [ 'name' ],
                [ 'nullable' ],
                [ 'bool' ],
                [ 'user' ]
            )
            ->willReturnCallback(function($id) {
                return '`' . $id . '`';
            });

        $insert = new Insert(new TableReference('user'), [
            'id', new FieldReference('name'), 'nullable', 'bool'
        ]);

        $container = new ParameterContainer();
        $query = $insert->toPreparableSql($specificationMock, $container);

        $containerParameters = (new ObjectInspector($container))->getValue('parameters');
        verify($containerParameters)->hasKey('id');
        verify($containerParameters)->hasKey('name');
        verify($containerParameters)->hasKey('nullable');
        verify($containerParameters)->hasKey('bool');

        verify($query)->equals(
            'INSERT INTO `user` (`id`, `name`, `nullable`, `bool`) ' .
            'VALUES (:id, :name, :nullable, :bool);'
        );
    }

    /**
     * @throws MissingColumnException
     */
    public function testToPreparableSqlWillThrowMissingColumnException()
    {
        $this->expectException(MissingColumnException::class);
        $specificationMock = $this->getMockForAbstractClass(Specification::class);

        $container = new ParameterContainer();
        $insert = new Insert(new TableReference('user'));
        $insert->toPreparableSql($specificationMock, $container);
    }

    public function testExecute()
    {
        $specificationMock = $this->getMockBuilder(Specification::class)
            ->setMethods([ 'quoteIdentifier', 'quoteValue' ])
            ->getMockForAbstractClass();

        $specificationMock->expects($this::exactly(3))
            ->method('quoteIdentifier')
            ->withConsecutive(['id'], ['name'], ['user'])
            ->willReturnOnConsecutiveCalls('`id`', '`name`', '`user`');

        $specificationMock->expects($this::never())->method('quoteValue');


        $driverMock = $this->getMockBuilder(Driver::class)
            ->setMethods([ 'getSpecification', 'prepare', 'getLastInsertedId', 'transactionStart', 'transactionCommit' ])
            ->getMockForAbstractClass();

        $statementMock = $this->getMockBuilder(PreparedStatement::class)
            ->setMethods(['setParameterContainer', 'execute'])
            ->getMockForAbstractClass();

        $statementMock->expects($this::once())
            ->method('setParameterContainer')
            ->with($this::callback(function($container) {
                verify($container)->isInstanceOf(ParameterContainerInterface::class);
                return true;
            }));

        $statementMock->expects($this::exactly(2))
            ->method('execute')
            ->willReturn($this->getMockForAbstractClass(Result::class));

        $driverMock->expects($this::once())
            ->method('getSpecification')
            ->willReturn($specificationMock);

        $driverMock->expects($this::once())->method('transactionStart');
        $driverMock->expects($this::once())->method('transactionCommit');

        $driverMock->expects($this::exactly(2))
            ->method('getLastInsertedId')
            ->willReturnOnConsecutiveCalls(2, 3);

        $driverMock->expects($this::once())
            ->method('prepare')
            ->with('INSERT INTO `user` (`id`, `name`) VALUES (:id, :name);')
            ->willReturn($statementMock);

        $insert = new Insert(new TableReference('user'), [ 'id', 'name' ]);
        $insert->addRow([ NULL, 'test' ]);
        $insert->addRow([ NULL, 'another test' ]);

        $insertedIds = $insert->execute($driverMock);
        verify($insertedIds)->equals([ 2, 3 ]);
    }

    public function testExecuteWillRollbackOnError()
    {
        $this->expectException(\Exception::class);

        $specificationMock = $this->getMockBuilder(Specification::class)
            ->setMethods([ 'quoteIdentifier', 'quoteValue' ])
            ->getMockForAbstractClass();

        $specificationMock->expects($this::exactly(3))
            ->method('quoteIdentifier')
            ->withConsecutive(['id'], ['name'], ['user'])
            ->willReturnOnConsecutiveCalls('`id`', '`name`', '`user`');

        $specificationMock->expects($this::never())->method('quoteValue');


        $driverMock = $this->getMockBuilder(Driver::class)
            ->setMethods([ 'getSpecification', 'prepare', 'getLastInsertedId', 'transactionStart', 'transactionAbort' ])
            ->getMockForAbstractClass();

        $statementMock = $this->getMockBuilder(PreparedStatement::class)
            ->setMethods(['setParameterContainer', 'execute'])
            ->getMockForAbstractClass();

        $statementMock->expects($this::once())
            ->method('setParameterContainer')
            ->with($this::callback(function($container) {
                verify($container)->isInstanceOf(ParameterContainerInterface::class);
                return true;
            }));

        $statementMock->expects($this::once())
            ->method('execute')
            ->willThrowException(new \Exception());

        $driverMock->expects($this::once())
            ->method('getSpecification')
            ->willReturn($specificationMock);

        $driverMock->expects($this::once())->method('transactionStart');
        $driverMock->expects($this::once())->method('transactionAbort');
        $driverMock->expects($this::never())->method('getLastInsertedId');

        $driverMock->expects($this::once())
            ->method('prepare')
            ->with('INSERT INTO `user` (`id`, `name`) VALUES (:id, :name);')
            ->willReturn($statementMock);

        $insert = new Insert(new TableReference('user'), [ 'id', 'name' ]);
        $insert->addRow([ NULL, 'test' ]);
        $insert->addRow([ NULL, 'another test' ]);

        $insert->execute($driverMock);
    }
}
