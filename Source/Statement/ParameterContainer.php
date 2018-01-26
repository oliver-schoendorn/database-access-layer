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
     * @param string $key
     * @param mixed  $value
     * @param int    $type
     *
     * @return static
     * @throws InvalidParameterTypeException
     * @throws InvalidParameterKeyException
     */
    public function setValue(string $key, $value, int $type)
    {
        $this->validateType($type);
        $key = $this->validateKey($key);

        $this->values[$key] = $value;
        $this->parameters[$key] = [ ':' . $key, $type ];

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  &$value
     * @param int    $type
     *
     * @return static
     * @throws InvalidParameterTypeException
     * @throws InvalidParameterKeyException
     */
    public function bindValue(string $key, &$value, int $type)
    {
        $this->validateType($type);
        $key = $this->validateKey($key);

        $this->values[$key] = &$value;
        $this->parameters[$key] = [ ':' . $key, $type ];
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
        $this->validateType($type);
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
     * @param int $type
     *
     * @throws InvalidParameterTypeException
     */
    private function validateType(int $type)
    {
        if ( ! in_array($type, [
            $this::TYPE_NULL,
            $this::TYPE_INT,
            $this::TYPE_BOOL,
            $this::TYPE_STRING,
            $this::TYPE_STREAM
        ])) {
            throw new InvalidParameterTypeException($type);
        }
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
}
