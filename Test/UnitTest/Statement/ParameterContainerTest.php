<?php

namespace OS\DatabaseAccessLayer\Test\UnitTest\Statement;


use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterKeyException;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException;
use OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException;
use OS\DatabaseAccessLayer\Statement\ParameterContainer;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;

class ParameterContainerTest extends UnitTestCase
{
    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     * @throws \ReflectionException
     */
    public function testSetValueWillStoreValue()
    {
        $container = new ParameterContainer();
        $container->setValue('randomKey1234', 'value', $container::TYPE_STRING);

        $inspector = new ObjectInspector($container);
        $parameters = $inspector->getValue('parameters');
        $values = $inspector->getValue('values');

        verify($parameters)->hasKey('randomKey1234');
        verify($parameters['randomKey1234'])->equals([
            ':randomKey1234',
            $container::TYPE_STRING
        ]);

        verify($values)->hasKey('randomKey1234');
        verify($values['randomKey1234'])->equals('value');
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    public function testSetValueWillRejectInvalidTypes()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $container = new ParameterContainer();
        $container->setValue('foo', 'bar', -1234);
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    public function testSetValueWillRejectInvalidKey()
    {
        $this->expectException(InvalidParameterKeyException::class);

        $container = new ParameterContainer();
        $container->setValue(':in valid', 'foo', $container::TYPE_STRING);
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     * @throws \ReflectionException
     */
    public function testSetValueWillOverwriteExistingKey()
    {
        $container = new ParameterContainer();
        $container->setValue('randomKey1234', 'anotherValue', $container::TYPE_STRING);
        $container->setValue('randomKey1234', 'value', $container::TYPE_STRING);

        $inspector = new ObjectInspector($container);
        $values = $inspector->getValue('values');
        $parameters = $inspector->getValue('parameters');

        verify($values)->hasKey('randomKey1234');
        verify($values['randomKey1234'])->equals('value');

        verify($parameters)->hasKey('randomKey1234');
        verify($parameters['randomKey1234'])->equals([
            ':randomKey1234',
            $container::TYPE_STRING
        ]);
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    public function testBindValueWillRejectInvalidType()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $container = new ParameterContainer();
        $value = 'bar';
        $container->bindValue('foo', $value, -1234);
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    public function testBindValueWillRejectInvalidKey()
    {
        $this->expectException(InvalidParameterKeyException::class);

        $container = new ParameterContainer();
        $value = 'bar';
        $container->bindValue(':in valid', $value, $container::TYPE_STRING);
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     * @throws \ReflectionException
     */
    public function testBindValueDoesPreserveReference()
    {
        $value = 'foo';
        $container = new ParameterContainer();
        $container->bindValue(':key', $value, $container::TYPE_STRING);
        $value = 'bar';

        $inspector  = new ObjectInspector($container);
        $values = $inspector->getValue('values');
        $parameters = $inspector->getValue('parameters');

        verify($parameters)->hasKey('key');
        verify($parameters['key'])->equals([
            ':key',
            $container::TYPE_STRING
        ]);

        verify($values)->hasKey('key');
        verify($values['key'])->equals('bar');
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     * @throws \ReflectionException
     */
    public function testBindValueWillOverwriteBinding()
    {
        $container = new ParameterContainer();

        $value = 'foo';
        $container->bindValue(':key', $value, $container::TYPE_STRING);

        $value2 = 'foo';
        $container->bindValue(':key', $value2, $container::TYPE_STRING);

        $value2 = 'bar';
        $inspector = new ObjectInspector($container);
        $parameters = $inspector->getValue('parameters');

        verify($parameters)->hasKey('key');
        verify($parameters['key'])->equals([
            ':key',
            $container::TYPE_STRING
        ]);

        $values = $inspector->getValue('values');
        verify($values)->hasKey('key');
        verify($values['key'])->equals('bar');
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    public function testAddParameterWillRejectInvalidType()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $container = new ParameterContainer();
        $value = 'bar';
        $container->addParameter('foo', -1234, $value);
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    public function testAddParameterWillRejectInvalidKey()
    {
        $this->expectException(InvalidParameterKeyException::class);

        $container = new ParameterContainer();
        $value = 'bar';
        $container->addParameter(':in valid', $container::TYPE_STRING, $value);
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     * @throws \ReflectionException
     */
    public function testAddParameterWillGenerateUniqueKeys()
    {
        $container = new ParameterContainer();
        $container->setValue('foo', 'bar', $container::TYPE_STRING);
        $container->addParameter('foo', $container::TYPE_STRING, 'bar');

        $container->addParameter('bar', $container::TYPE_STRING, 'baz1');
        $container->addParameter('bar', $container::TYPE_STRING, 'baz2');

        $parameters = (new ObjectInspector($container))->getValue('parameters');

        verify($parameters)->hasKey('foo');
        verify($parameters)->hasKey('foo_01');
        verify($parameters['foo'])->equals([ ':foo', $container::TYPE_STRING ]);
        verify($parameters['foo_01'])->equals([ ':foo_01', $container::TYPE_STRING ]);

        verify($parameters)->hasKey('bar_01');
        verify($parameters)->hasKey('bar_02');
        verify($parameters['bar_01'])->equals([ ':bar_01', $container::TYPE_STRING ]);
        verify($parameters['bar_02'])->equals([ ':bar_02', $container::TYPE_STRING ]);
    }

    public function testGetParametersWillReturnAllParameters()
    {
        $container = new ParameterContainer();
        $value = 4;
        $container->setValue('test', 'value', $container::TYPE_STRING);
        $container->bindValue('test2', $value, $container::TYPE_INT);
        $container->addParameter('test', $container::TYPE_BOOL, 'value2');

        $parameters = $container->getParameters();

        verify(count($parameters))->equals(3);
        verify($parameters['test'])->equals([ ':test', 'value', $container::TYPE_STRING ]);
        verify($parameters['test2'])->equals([ ':test2', 4, $container::TYPE_INT ]);
        verify($parameters['test_01'])->equals([ ':test_01', 'value2', $container::TYPE_BOOL ]);
    }

    /**
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     * @throws MissingParameterValueException
     */
    public function testGetParametersWillThrowExceptionOnUndefinedValue()
    {
        $this->expectException(MissingParameterValueException::class);

        $container = new ParameterContainer();
        $container->addParameter('test', $container::TYPE_STRING, null);
        $container->getParameters();
    }

    public function validTypeDataProvider()
    {
        return [
            'TYPE_NULL' => [ ParameterContainer::TYPE_NULL ],
            'TYPE_INT' => [ ParameterContainer::TYPE_INT ],
            'TYPE_BOOL' => [ ParameterContainer::TYPE_BOOL ],
            'TYPE_STRING' => [ ParameterContainer::TYPE_STRING ],
            'TYPE_STREAM' => [ ParameterContainer::TYPE_STREAM ]
        ];
    }

    /**
     * @param int $type
     *
     * @throws InvalidParameterTypeException
     * @throws InvalidParameterKeyException
     *
     * @dataProvider validTypeDataProvider
     */
    public function testValidateTypes(int $type)
    {
        $container = new ParameterContainer();
        $container->setValue('foo', 'bar', $type);
        verify(true)->true();
    }
}
