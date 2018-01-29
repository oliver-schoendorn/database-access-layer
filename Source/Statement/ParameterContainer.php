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

/**
 * Class ParameterContainer
 *
 * @package OS\DatabaseAccessLayer\Statement
 */
class ParameterContainer implements ParameterContainerInterface
{
    /**
     * @var array[]
     */
    private $parameters = [];

    /**
     * @var mixed[]
     */
    private $values = [];

    /**
     * @param string   $key
     * @param mixed    $value
     * @param int|null $type
     *
     * @return static
     * @throws InvalidParameterTypeException
     * @throws InvalidParameterKeyException
     */
    public function setValue(string $key, $value, int $type = null)
    {
        $key = $this->validateKey($key);
        $this->values[$key] = $value;

        if ($type !== null || ! array_key_exists($key, $this->parameters)) {
            $type = $type ?? static::guessParameterType($value);
            static::validateType($type);
            $this->parameters[$key] = [ ':' . $key, $type ];
        }

        return $this;
    }

    /**
     * @param string   $key
     * @param mixed    &$value
     * @param int|null $type
     *
     * @return static
     * @throws InvalidParameterTypeException
     * @throws InvalidParameterKeyException
     */
    public function bindValue(string $key, &$value, int $type = null)
    {
        $key = $this->validateKey($key);
        $this->values[$key] = &$value;

        if ( ! array_key_exists($key, $this->parameters)) {
            $type = $type ?? static::guessParameterType($value);
            static::validateType($type);
            $this->parameters[$key] = [ ':' . $key, $type ];
        }

        return $this;
    }

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
    public function addParameter(string $key, int $type, $value = null): string
    {
        static::validateType($type);
        $key = $this->validateKey($key);
        $key = $this->getUniqueParameterName($key);

        $this->parameters[$key] = [ ':' . $key, $type ];
        if ($value || $type === $this::TYPE_NULL) {
            $this->values[$key] = $value;
        }

        return $key;
    }

    /**
     * Generates a unique parameter name
     *
     * @param string $key
     *
     * @return string
     */
    private function getUniqueParameterName(string $key): string
    {
        if ( ! array_key_exists($key, $this->parameters)) {
            return $key;
        }

        $i = 0;
        do {
            ++$i;
            $tmpName = sprintf('%s_%\'.02d', $key, $i);
        } while(array_key_exists($tmpName, $this->parameters));

        return $tmpName;
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws InvalidParameterKeyException
     */
    private function validateKey(string $key): string
    {
        $key = ltrim($key, ':');
        if ( ! preg_match('/^[a-zA-Z0-9]{1,}$/', $key)) {
            throw new InvalidParameterKeyException($key);
        }

        return $key;
    }

    /**
     * Must return an associative array.
     *
     * Example:
     * return [ 'paramKey' => [ ':paramKey', 'value', $this::TYPE_STRING ], ... ]
     *
     * @return array[]
     * @throws MissingParameterValueException
     */
    public function getParameters(): array
    {
        $response = [];
        foreach ($this->parameters as $key => $parameter) {
            if ($parameter[1] !== static::TYPE_NULL && ! array_key_exists($key, $this->values)) {
                throw new MissingParameterValueException($key);
            }

            $response[$key] = [ $parameter[0], $this->values[$key] ?? null, $parameter[1] ];
        }

        return $response;
    }

    /**
     * @param int $type
     *
     * @throws InvalidParameterTypeException
     */
    public static function validateType(int $type)
    {
        if ( ! in_array($type, [
            static::TYPE_NULL,
            static::TYPE_INT,
            static::TYPE_BOOL,
            static::TYPE_STRING,
            static::TYPE_STREAM
        ])) {
            throw new InvalidParameterTypeException($type);
        }
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    public static function guessParameterType($value): int
    {
        switch (true) {
            case is_null($value):
                $type = static::TYPE_NULL;
                break;

            case is_int($value):
                $type = static::TYPE_INT;
                break;

            case is_bool($value):
                $type = static::TYPE_BOOL;
                break;

            case is_string($value):
                $type = static::TYPE_STRING;
                break;

            case is_resource($value):
                $type = static::TYPE_STREAM;
                break;
        }

        return $type ?? static::TYPE_STRING;
    }
}
