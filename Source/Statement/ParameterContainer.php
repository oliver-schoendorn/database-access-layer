<?php

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
        foreach ($this->parameters as $key => $parameter) {
            if ($parameter[2] !== static::TYPE_NULL && is_null($parameter[1])) {
                throw new MissingParameterValueException($key);
            }
        }

        return $this->parameters;
    }
}
