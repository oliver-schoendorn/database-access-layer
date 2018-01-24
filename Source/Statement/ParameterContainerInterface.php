<?php

namespace OS\DatabaseAccessLayer\Statement;


use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException;
use OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException;

interface ParameterContainerInterface
{
    /**
     * Available parameter types
     */
    const TYPE_NULL = 0;
    const TYPE_INT = 10;
    const TYPE_BOOL = 20;
    const TYPE_STRING = 30;
    const TYPE_STREAM = 40;

    /**
     * Stores a value for the execution of a prepared statement
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $type
     *
     * @return static
     * @throws InvalidParameterTypeException
     */
    public function setValue(string $key, $value, int $type);

    /**
     * Binds a reference to a value for the execution of a prepared statement
     *
     * @param string $key
     * @param mixed  &$value
     * @param int    $type
     *
     * @return static
     * @throws InvalidParameterTypeException
     */
    public function bindValue(string $key, &$value, int $type);

    /**
     * Registers a new parameter type and generates a unique parameter key
     *
     * @param string     $key
     * @param int        $type
     * @param mixed|null $value
     *
     * @return string
     */
    public function addParameter(string $key, int $type, $value = null): string;

    /**
     * Must return an associative array.
     *
     * Example:
     * return [ 'paramKey' => [ ':paramKey', 'value', $this::TYPE_STRING ], ... ]
     *
     * @return array[]
     * @throws MissingParameterValueException
     */
    public function getParameters(): array;
}
