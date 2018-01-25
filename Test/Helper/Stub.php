<?php

namespace OS\DatabaseAccessLayer\Test\Helper;


class Stub
{
    private $instance;

    private function __construct(string $className, array $properties = [])
    {
        $reflection = new \ReflectionClass($className);
        $instance = $reflection->newInstanceWithoutConstructor();

        if (count($properties) > 0) {
            $inspector = new ObjectInspector($instance);
            foreach ($properties as $key => $value) {
                $inspector->setValue($key, $value);
            }
        }

        $this->instance = $instance;
    }

    public static function make(string $className, array $properties = [])
    {
        return (new static($className, $properties))->instance;
    }
}
