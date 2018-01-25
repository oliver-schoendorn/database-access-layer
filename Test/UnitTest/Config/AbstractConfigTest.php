<?php

namespace OS\DatabaseAccessLayer\Test\UnitTest\Config;


use OS\DatabaseAccessLayer\Config\AbstractConfig;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AbstractConfigTest extends UnitTestCase
{
    /**
     * @param array $properties
     *
     * @return AbstractConfig|MockObject
     */
    private function stubAbstractConfig($properties = []): AbstractConfig
    {
        $mock = $this->getMockBuilder(AbstractConfig::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        foreach($properties as $key => $value) {
            $mock->{$key} = $value;
        }

        return $mock;
    }

    public function testConstructorWillInitializeProperties()
    {
        $configStub = $this->stubAbstractConfig([ 'foo' => null, 'baz' => null ]);
        verify('Initial State', $configStub->foo)->null();
        verify('Initial State', $configStub->baz)->null();

        $expectedValues = [
            'foo' => 'bar',
            'baz' => [
                'foo',
                'bar'
            ]
        ];

        $configStub->__construct($expectedValues);
        verify('Constructor did update properties',
            [ 'foo' => $configStub->foo, 'baz' => $configStub->baz ])->equals($expectedValues);
    }

    public function testConstructorWillNotInitializeUnknownProperties()
    {
        $configStub = $this->stubAbstractConfig([ 'foo' => null ]);
        verify('Initial State', $configStub->foo)->null();

        $configStub->__construct([
            'foo' => true,
            'bar' => true
        ]);

        verify('constructor did not initialize unknown property',
            property_exists($configStub, 'bar'))->false();
    }

    public function toArrayDataProvider()
    {
        return [
            [ ['foo' => true, 'bar' => 'baz', 'null' => null ] ]
        ];
    }

    /**
     * @param array $testData
     *
     * @dataProvider toArrayDataProvider
     */
    public function testToArrayWillReturnAllProperties(array $testData)
    {
        $configStub = $this->stubAbstractConfig([ 'foo' => null, 'bar' => null, 'null' => false ]);
        $configStub->__construct($testData);

        $response = $configStub->toArray();

        verify('Array equals the input array', $response)->equals($testData);
    }
}
