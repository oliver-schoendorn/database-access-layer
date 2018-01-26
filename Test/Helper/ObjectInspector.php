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
