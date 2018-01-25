<?php

namespace OS\DatabaseAccessLayer\Test\Helper;


use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

class ObjectInspector
{
    /**
     * @var object
     */
    private $instance;

    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * ObjectInspector constructor.
     *
     * @param object $instance
     * @throws \ReflectionException
     */
    public function __construct($instance)
    {
        $this->instance = $instance;
        $this->reflection = new ReflectionClass($instance);

        if ($instance instanceof MockObject) {
            $parentReflection = $this->reflection->getParentClass();
            $this->reflection = $parentReflection ?: $this->reflection;
        }
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function invoke(string $method, array $args = [])
    {
        $methodReflection = $this->reflection->getMethod($method);
        $methodReflection->setAccessible(true);

        return $methodReflection->invokeArgs($this->instance, $args);
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function getValue(string $property)
    {
        $propertyReflection = $this->reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($this->instance);
    }

    /**
     * @param string $property
     * @param mixed  $value
     */
    public function setValue(string $property, $value)
    {
        $propertyReflection = $this->reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        $propertyReflection->setValue($this->instance, $value);
    }
}
