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

namespace OS\DatabaseAccessLayer\Statement;


use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterKeyException;
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
     * @param int $type
     *
     * @throws InvalidParameterTypeException
     */
    public static function validateType(int $type);

    /**
     * @param mixed $value
     *
     * @return int
     */
    public static function guessParameterType($value): int;

    /**
     * Stores a value for the execution of a prepared statement
     *
     * @param string $key
     * @param mixed  $value
     * @param int|null $type
     *
     * @return static
     * @throws InvalidParameterTypeException
     */
    public function setValue(string $key, $value, int $type = null);

    /**
     * Binds a reference to a value for the execution of a prepared statement
     *
     * @param string   $key
     * @param mixed    &$value
     * @param int|null $type
     *
     * @return static
     * @throws InvalidParameterTypeException
     */
    public function bindValue(string $key, &$value, int $type = null);

    /**
     * Registers a new parameter type and generates a unique parameter key
     *
     * @param string     $key
     * @param int        $type
     * @param mixed|null $value
     *
     * @return string
     * @throws InvalidParameterTypeException
     * @throws InvalidParameterKeyException
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
