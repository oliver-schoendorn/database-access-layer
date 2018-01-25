<?php

namespace OS\DatabaseAccessLayer\Test\UnitTest\Driver\MysqlPdo;


use OS\DatabaseAccessLayer\Config\DatabaseConfig;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Driver;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement\PreparedStatement;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement\Result;
use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use OS\DatabaseAccessLayer\Test\Helper\Stub;

class DriverTest extends UnitTestCase
{
    /**
     * @return MockBuilder
     */
    private function getDriverMockBuilder(): MockBuilder
    {
        return $this->getMockBuilder(Driver::class);
    }

    /**
     * @return Driver|MockObject
     */
    private function getDriverStub(): Driver
    {
        return $this->getDriverMockBuilder()
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();
    }

    private function getConfigStub(bool $debug = false): DatabaseConfig
    {
        return new DatabaseConfig([
            'host' => 'testHostName',
            'port' => 123456,
            'name' => 'fancyDatabaseName',
            'charset' => 'whoNeedsLettersIfOneCanHaveEmojis',
            'debug' => $debug
        ]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testBuildDsnStringContainsAllParameters()
    {
        $driverStub = $this->getDriverStub();
        $driverConfig = $this->getConfigStub();

        $dsnString = (new ObjectInspector($driverStub))->invoke('buildDsnString', [ $driverConfig ]);

        verify($dsnString)->startsWith('mysql:');
        verify($dsnString)->contains('host=testHostName');
        verify($dsnString)->contains('port=123456');
        verify($dsnString)->contains('dbname=fancyDatabaseName');
        verify($dsnString)->contains('charset=whoNeedsLettersIfOneCanHaveEmojis');
    }

    /**
     * @throws \ReflectionException
     */
    public function testBuildDsnStringDoesNotContainUndefinedParameters()
    {
        $driverStub = $this->getDriverStub();
        $driverConfig = new DatabaseConfig([
            'host' => 'testHostName',
            'charset' => 'whoNeedsLettersIfOneCanHaveEmojis'
        ]);

        $dsnString = (new ObjectInspector($driverStub))->invoke('buildDsnString', [ $driverConfig ]);

        verify($dsnString)->startsWith('mysql:');
        verify($dsnString)->contains('host=testHostName');
        verify($dsnString)->notContains('port=');
        verify($dsnString)->notContains('dbname=');
        verify($dsnString)->contains('charset=whoNeedsLettersIfOneCanHaveEmojis');
    }

    /**
     *
     */
    public function testEscapeWillDelegateCallToPdo()
    {
        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'quote' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('quote')
            ->with('testString', 1234)
            ->willReturn('testString');


        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [ 'pdo' => $pdoMock ]);

        $response = $driverMock->escape('testString', 1234);
        verify($response)->equals('testString');
    }

    /**
     *
     */
    public function testGetLastInsertedId()
    {
        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'lastInsertId' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('lastInsertId')
            ->willReturn(12);

        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [ 'pdo' => $pdoMock ]);

        $response = $driverMock->getLastInsertedId();
        verify($response)->equals(12);
    }

    /**
     *
     */
    public function testTransactionStart()
    {
        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'beginTransaction' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('beginTransaction')
            ->willReturn(true);

        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [ 'pdo' => $pdoMock ]);

        $response = $driverMock->transactionStart();
        verify($response)->true();
    }

    /**
     *
     */
    public function testTransactionAbort()
    {
        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'rollBack' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('rollBack')
            ->willReturn(true);

        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [ 'pdo' => $pdoMock ]);

        $response = $driverMock->transactionAbort();
        verify($response)->true();
    }

    /**
     *
     */
    public function testTransactionCommit()
    {

        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'commit' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('commit')
            ->willReturn(true);

        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [ 'pdo' => $pdoMock ]);

        $response = $driverMock->transactionCommit();
        verify($response)->true();
    }

    /**
     * @throws StatementExecutionException
     * @throws \ReflectionException
     */
    public function testQueryWillDelegateCallToPdo()
    {
        $expectedQuery = 'SELECT * FROM foo;';
        $expectedResponse = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'query' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('query')
            ->with($expectedQuery)
            ->willReturn($expectedResponse);

        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [ 'pdo' => $pdoMock ]);

        $response = $driverMock->query($expectedQuery);
        verify($response)->isInstanceOf(Result::class);
        verify((new ObjectInspector($response))->getValue('statement'))->same($expectedResponse);
    }

    /**
     * @throws StatementExecutionException
     */
    public function testQueryWillCatchExceptions()
    {
        $this->expectException(StatementExecutionException::class);

        $expectedQuery = 'SELECT * FROM foo;';

        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'query' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('query')
            ->with($expectedQuery)
            ->willReturn(null);

        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [
            'pdo' => $pdoMock,
            'config' => $this->getConfigStub()
        ]);
        $driverMock->query($expectedQuery);
    }

    /**
     * @throws StatementPreparationException
     * @throws \ReflectionException
     */
    public function testPrepareWillDelegateCallToPdo()
    {
        $expectedQuery = 'SELECT * FROM foo;';
        $expectedResponse = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'prepare' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('prepare')
            ->with($expectedQuery)
            ->willReturn($expectedResponse);

        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [
            'pdo' => $pdoMock,
            'config' => $this->getConfigStub()
        ]);

        $response = $driverMock->prepare($expectedQuery);
        verify($response)->isInstanceOf(PreparedStatement::class);
        verify((new ObjectInspector($response))->getValue('statement'))->same($expectedResponse);
    }

    /**
     * @throws StatementPreparationException
     */
    public function testPrepareWillCatchExceptions()
    {
        $this->expectException(StatementPreparationException::class);
        $expectedQuery = 'SELECT * FROM foo;';

        $pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'prepare' ])
            ->getMock();

        $pdoMock->expects($this::once())
            ->method('prepare')
            ->with($expectedQuery)
            ->willReturn(null);

        /** @var Driver|MockObject $driverMock */
        $driverMock = Stub::make(Driver::class, [
            'pdo' => $pdoMock,
            'config' => $this->getConfigStub()
        ]);
        $driverMock->prepare($expectedQuery);
    }
}
