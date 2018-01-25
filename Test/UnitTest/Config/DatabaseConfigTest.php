<?php

namespace OS\DatabaseAccessLayer\Test\UnitTest\Config;


use OS\DatabaseAccessLayer\Config\DatabaseConfig;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;

class DatabaseConfigTest extends UnitTestCase
{
    public function testConstructorWithAllValues()
    {
        $testData = [
            'host' => 'host',
            'port' => 'port',
            'name' => 'name',
            'username' => 'username',
            'password' => 'password',
            'charset' => 'charset',
            'debug' => 'debug'
        ];

        $databaseConfig = new DatabaseConfig($testData);
        verify($databaseConfig->toArray())->equals($testData);
    }

    public function testConstructorDefaultValues()
    {
        $databaseConfig = new DatabaseConfig([]);
        verify($databaseConfig->toArray())->equals([
            'host' => '',
            'port' => -1,
            'name' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8mb4',
            'debug' => false
        ]);
    }

    public function getterDataProvider()
    {
        return [
            'host' => [ 'getHost', 'host' ],
            'name' => [ 'getName', 'name' ],
            'port' => [ 'getPort', 'port', 12345 ],
            'username' => [ 'getUsername', 'username' ],
            'password' => [ 'getPassword', 'password' ],
            'charset' => [ 'getCharset', 'charset' ],
            'debug' => [ 'isDebug', 'debug', true ]
        ];
    }

    /**
     * @param string $getter
     * @param string $key
     * @param mixed|null $expectedValue
     *
     * @dataProvider getterDataProvider
     */
    public function testGettersReturnCorrectValues(string $getter, string $key, $expectedValue = null)
    {
        $databaseConfig = new DatabaseConfig([ $key => $expectedValue ?? $key ]);
        verify($databaseConfig->{$getter}())->equals($expectedValue ?? $key);
    }
}
