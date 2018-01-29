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


use OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement\PreparedStatement;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement\Result;
use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;
use OS\DatabaseAccessLayer\Statement\ParameterContainer;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\Helper\Stub;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PreparedStatementTest extends UnitTestCase
{
    /**
     * @return \PDOStatement|MockObject
     */
    private function getPdoStatementMock(): \PDOStatement
    {
        return $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->setMethodsExcept([])
            ->getMock();
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException
     */
    public function testExecuteWillDelegateExecutionToPdo()
    {
        $pdoStatement = $this->getPdoStatementMock();
        $pdoStatement->expects($this::once())
            ->method('execute')
            ->willReturn(true);

        $statement = new PreparedStatement($pdoStatement);
        $result = $statement->execute();
        verify($result)->isInstanceOf(Result::class);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterKeyException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException
     * @throws \ReflectionException
     */
    public function testExecuteWillBindParameters()
    {
        $pdoStatement = $this->getPdoStatementMock();
        $pdoStatement->expects($this::once())
            ->method('execute')
            ->willReturn(true);

        $pdoStatement->expects($this::exactly(8))
            ->method('bindParam')
            ->withConsecutive(
                [ ':test', 'test', \PDO::PARAM_STR ],
                [ ':test2', 'test2', \PDO::PARAM_INT ],
                [ ':test_01', 'foo', \PDO::PARAM_NULL ],
                [ ':test_02', 'foo', \PDO::PARAM_INT ],
                [ ':test_03', 'foo', \PDO::PARAM_BOOL ],
                [ ':test_04', 'foo', \PDO::PARAM_STR ],
                [ ':test_05', 'foo', \PDO::PARAM_LOB ],
                [ ':test_06', 'foo', \PDO::PARAM_LOB ]
            );

        $statement = new PreparedStatement($pdoStatement);
        $container = new ParameterContainer();

        $container->bindValue('test', $test, $container::TYPE_STRING);
        $container->setValue('test2', 'test2', $container::TYPE_INT);

        $container->addParameter('test', $container::TYPE_NULL, 'foo');
        $container->addParameter('test', $container::TYPE_INT, 'foo');
        $container->addParameter('test', $container::TYPE_BOOL, 'foo');
        $container->addParameter('test', $container::TYPE_STRING, 'foo');
        $container->addParameter('test', $container::TYPE_STREAM, 'foo');
        $container->addParameter('test', $container::TYPE_STREAM, 'foo');

        $parameters = (new ObjectInspector($container))->getValue('parameters');
        $parameters['test_06'][2] = 12345;
        (new ObjectInspector($container))->setValue('parameters', $parameters);

        /** @noinspection PhpUnusedLocalVariableInspection */
        $test   = 'test';
        $result = $statement->execute($container);
        verify($result)->isInstanceOf(Result::class);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException
     */
    public function testExecuteWillHandleExceptions()
    {
        $pdoStatement = $this->getPdoStatementMock();
        $pdoStatement->expects($this::once())
            ->method('execute')
            ->willThrowException(new \PDOException('testException'));


        $statement = new PreparedStatement($pdoStatement);

        try { $statement->execute(); }
        catch (StatementExecutionException $exception) {
            verify($exception->getMessage())->notContains('testException');
        }
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException
     */
    public function testExecuteWillHandleExceptionsInDebugMode()
    {
        $pdoStatement = $this->getPdoStatementMock();
        $pdoStatement->expects($this::once())
            ->method('execute')
            ->willThrowException(new \PDOException('testException'));

        $statement = new PreparedStatement($pdoStatement, null, true);

        try { $statement->execute(); }
        catch (StatementExecutionException $exception) {
            verify($exception->getMessage())->contains('testException');
        }
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException
     */
    public function testExecuteWillHandleExecutionExceptions()
    {
        $pdoStatement = $this->getPdoStatementMock();
        $pdoStatement->expects($this::once())
            ->method('execute')
            ->willReturn(false);

        $pdoStatement->expects($this::once())
            ->method('errorInfo')
            ->willReturn([ 'foo', 123, 'bar' ]);

        $statement = new PreparedStatement($pdoStatement);

        try { $statement->execute(); }
        catch (StatementExecutionException $exception) {
            verify($exception->getCode())->notEquals(123);
            verify($exception->getMessage())->notContains('bar');
        }
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException
     */
    public function testExecuteWillHandleExecutionExceptionsInDebugMode()
    {
        $pdoStatement = $this->getPdoStatementMock();
        $pdoStatement->expects($this::once())
            ->method('execute')
            ->willReturn(false);

        $pdoStatement->expects($this::once())
            ->method('errorInfo')
            ->willReturn([ 'foo', 123, 'bar' ]);

        $statement = new PreparedStatement($pdoStatement, null, true);

        try { $statement->execute(); }
        catch (StatementExecutionException $exception) {
            verify($exception->getCode())->equals(123);
            verify($exception->getMessage())->contains('bar');
        }
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException
     * @throws \ReflectionException
     */
    public function testGetDebugDataWithoutParameterContainer()
    {
        $pdoStatement = Stub::make(\PDOStatement::class);
        $statement = new PreparedStatement($pdoStatement);

        $query = 'SELECT * FROM foo;';
        (new ObjectInspector($statement))->setValue(
            'statement', (object) [ 'queryString' => $query]);

        verify($statement->getDebugData())->equals($query);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterKeyException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException
     * @throws \ReflectionException
     */
    public function testGetDebugDataWithParameterContainer()
    {
        $pdoStatement = Stub::make(\PDOStatement::class);
        $statement = new PreparedStatement($pdoStatement);

        $query = 'SELECT * FROM foo WHERE user = :string AND baz = :null AND bar = :int AND bool = :bool AND stream = :stream;';
        (new ObjectInspector($statement))->setValue(
            'statement', (object) [ 'queryString' => $query]);

        $stream = fopen('php://memory', 'r+');
        fputs($stream, 'stream contents');

        $container = new ParameterContainer();
        $container->setValue('null', null, $container::TYPE_NULL);
        $container->setValue('int', 123, $container::TYPE_INT);
        $container->setValue('bool', true, $container::TYPE_BOOL);
        $container->setValue('string', 'Foo Bar', $container::TYPE_STRING);
        $container->setValue('stream', $stream, $container::TYPE_STREAM);

        verify($statement->getDebugData($container))->equals(
            'SELECT * FROM foo WHERE user = "Foo Bar" AND baz = NULL AND bar = 123 ' .
            'AND bool = TRUE AND stream = "stream contents";'
        );
    }
}
