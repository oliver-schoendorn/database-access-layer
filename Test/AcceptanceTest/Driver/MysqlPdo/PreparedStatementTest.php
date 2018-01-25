<?php

namespace OS\DatabaseAccessLayer\Test\AcceptanceTest\Driver\MysqlPdo;


use OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement\PreparedStatement;
use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;
use OS\DatabaseAccessLayer\Statement\ParameterContainer;
use OS\DatabaseAccessLayer\Statement\Result;
use OS\DatabaseAccessLayer\Test\Helper\Fixture\UserTable;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\Helper\Stub;
use OS\DatabaseAccessLayer\Test\TestCase\AcceptanceTestCase;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use PHPUnit\DbUnit\DataSet\IDataSet;

class PreparedStatementTest extends AcceptanceTestCase
{
    /**
     * @var UserTable
     */
    private $table;

    protected function init()
    {
        $this->table = new UserTable($this->getConnection());
    }

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        return new ArrayDataSet([ $this->table->getTableName() => [
            [
                'id' => 1,
                'name' => 'User Name 01',
                'password' => hex2bin(md5('wtf')),
                'created' => '2018-01-25 09:00:00',
                'nullable' => null,
                'bool' => 1
            ],
            [
                'id' => 2,
                'name' => 'User Name 02',
                'password' => hex2bin(md5('wtf')),
                'created' => '2018-01-25 08:00:00',
                'nullable' => 12,
                'bool' => 1
            ],
            [
                'id' => 3,
                'name' => 'User Name 03',
                'password' => hex2bin(md5('wtf')),
                'created' => '2018-01-25 07:00:00',
                'nullable' => null,
                'bool' => 0
            ],
        ]]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructorWillInitializeAndStoreValues()
    {
        $statement = Stub::make(\PDOStatement::class);
        $preparedStatement = new PreparedStatement($statement, true);
        $inspector = new ObjectInspector($preparedStatement);

        verify($inspector->getValue('statement'))->same($statement);
        verify($inspector->getValue('debug'))->true();
    }

    /**
     * @throws \Exception
     */
    public function testExecuteWithoutParameters()
    {
        $pdoStatement = $this->getConnection()->getConnection()
            ->prepare('SELECT * FROM `' . $this->table->getTableName() . '`;');
        $statement = new PreparedStatement($pdoStatement, true);

        $result = $statement->execute();
        verify($result)->isInstanceOf(Result::class);
        verify($result->count())->equals(3);
    }

    /**
     * @throws \Exception
     */
    public function testExecuteWithParameters()
    {
        $pdoStatement = $this->getConnection()->getConnection()
            ->prepare('SELECT * FROM `' . $this->table->getTableName() . '` WHERE `name` = :name;');
        $statement = new PreparedStatement($pdoStatement, true);

        $container = new ParameterContainer();
        $container->setValue('name', 'User Name 03', $container::TYPE_STRING);

        $result = $statement->execute($container);
        verify($result)->isInstanceOf(Result::class);
        verify($result->count())->equals(1);
    }

    /**
     * @throws \Exception
     */
    public function testExecuteWithInvalidParameter()
    {
        $pdoStatement = $this->getConnection()->getConnection()
            ->prepare('SELECT * FROM `unknown` WHERE `name` = :name;');
        $statement = new PreparedStatement($pdoStatement, true);

        $container = new ParameterContainer();
        $container->setValue('name', 'User Name 03', $container::TYPE_STRING);

        try {
            $statement->execute($container);
            verify(false)->equals('Expected exception');
        }
        catch (\Exception $exception) {
            verify($exception)->isInstanceOf(StatementExecutionException::class);
            verify($exception->getCode())->equals(1013);
            verify($exception->getPrevious())->isInstanceOf(\PDOException::class);
        }
    }

    public function parameterTypeDataProvider()
    {
        $stream = fopen('php://memory', 'r+');
        fputs($stream, 'User Name 01');
        fseek($stream, 0);

        return [
            'null' => [ 'var', null, ParameterContainer::TYPE_NULL, '`nullable` = :var', 2 ],
            'int' => [ 'id', 0, ParameterContainer::TYPE_INT, '`id` != :id', 3 ],
            'bool' => [ 'id', true, ParameterContainer::TYPE_BOOL, '`bool` = :id', 2 ],
            'string' => [ 'string', 'User Name 02', ParameterContainer::TYPE_STRING, '`name` = :string', 1 ],
            'stream' => [ 'stream', $stream, ParameterContainer::TYPE_STREAM, '`name` = :stream', 1 ],
        ];
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $valueType
     * @param string $condition
     * @param int    $expectedRows
     *
     * @throws \Exception
     *
     * @dataProvider parameterTypeDataProvider
     */
    public function testExecuteWithAllParameterTypes(string $key, $value, int $valueType, string $condition, int $expectedRows)
    {
        // Unable to select by null
        if (is_null($value)) {
            verify(true)->true();
            return;
        }

        $container = new ParameterContainer();
        $container->setValue($key, $value, $valueType);

        $pdoStatement = $this->getConnection()->getConnection()
            ->prepare('SELECT * FROM `' . $this->table->getTableName() . '` WHERE ' . $condition . ';');
        $statement = new PreparedStatement($pdoStatement, true);

        $result = $statement->execute($container);
        verify($result->count())->equals($expectedRows);
    }

    public function testInsertWithNullParameter()
    {
        $pdoStatement = $this->getConnection()->getConnection()->prepare('
            INSERT INTO `' . $this->table->getTableName() . '` SET
                `name` = :name,
                `password` = :password,
                `created` = NOW(),
                `nullable` = :nullable,
                `bool` = TRUE;        
        ');

        $container = new ParameterContainer();
        $container->setValue('name', 'Test Name 01', $container::TYPE_STRING);
        $container->setValue('password', hex2bin(md5(rand(0,9999))), $container::TYPE_STRING);
        $container->setValue('nullable', null, $container::TYPE_NULL);

        $preparedStatement = new PreparedStatement($pdoStatement, true);

        $result = $preparedStatement->execute($container);
        verify($result->count())->equals(1);

        $insertedRowId = (int) $this->getConnection()->getConnection()->lastInsertId();
        $pdoStatement = $this->getConnection()->getConnection()
            ->query('SELECT `id`, `nullable` FROM `' . $this->table->getTableName() . '` WHERE `name` = "Test Name 01";');
        $row = $pdoStatement->fetchObject();

        verify((int) $row->id)->equals($insertedRowId);
        verify($row->nullable)->null();
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $valueType
     * @param string $condition
     * @param int    $expectedRows
     *
     * @throws \Exception
     *
     * @dataProvider parameterTypeDataProvider
     */
    public function testGetDebugData(string $key, $value, int $valueType, string $condition, int $expectedRows)
    {
        $container = new ParameterContainer();
        $container->setValue($key, $value, $valueType);

        $pdoStatement = $this->getConnection()->getConnection()
            ->prepare('SELECT * FROM `' . $this->table->getTableName() . '` WHERE ' . $condition . ';');
        $statement = new PreparedStatement($pdoStatement, true);

        if ($valueType === $container::TYPE_NULL) {
            $value = 'NULL';
        }
        else if ($valueType === $container::TYPE_BOOL) {
            $value = $value ? 'TRUE' : 'FALSE';
        }
        else if ($valueType === $container::TYPE_STREAM) {
            rewind($value);
            $value = stream_get_contents($value);
        }

        $result = $statement->getDebugData();
        verify($result)->contains(':' . $key);
        verify($result)->notContains((string) $value);

        $result = $statement->getDebugData($container);
        verify($result)->notContains(':' . $key);
        verify($result)->contains((string) $value);
    }
}
