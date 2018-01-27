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

namespace OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement;


use OS\DatabaseAccessLayer\Statement\Result as StatementResult;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidFetchTypeException;

class Result implements StatementResult
{
    /**
     * @var \PDOStatement
     */
    private $statement;

    /**
     * @var int
     */
    private $pointer = 0;

    /**
     * @var array|\stdClass|null
     */
    private $current = null;

    /**
     * @var int
     */
    private $iteratorFetchType = StatementResult::ITERATOR_FETCH_OBJECT;

    /**
     * PdoStatement constructor.
     *
     * @param \PDOStatement $statement
     * @param int $iteratorFetchType
     *
     * @throws InvalidFetchTypeException
     *
     * @codeCoverageIgnore
     */
    public function __construct(\PDOStatement $statement, int $iteratorFetchType = Result::ITERATOR_FETCH_OBJECT)
    {
        $this->statement = $statement;
        $this->setIteratorFetchType($iteratorFetchType);
    }

    /**
     * Defines the return type of the @see Statement::current() method
     *
     * Use the ITERATOR_FETCH_* constants as argument.
     *
     * @param int $iteratorFetchType
     *
     * @return static
     * @throws InvalidFetchTypeException
     */
    public function setIteratorFetchType(int $iteratorFetchType)
    {
        if ( ! in_array($iteratorFetchType, [ $this::ITERATOR_FETCH_ASSOC, $this::ITERATOR_FETCH_OBJECT ])) {
            throw new InvalidFetchTypeException($iteratorFetchType);
        }

        $this->iteratorFetchType = $iteratorFetchType;

        return $this;
    }

    /**
     * @return int
     */
    public function getIteratorFetchType(): int
    {
        return $this->iteratorFetchType;
    }

    /**
     * Return the current element
     * @link  http://php.net/manual/en/iterator.current.php
     * @return mixed|null Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        if ( ! $this->current && $this->pointer === 0) {
            $this->fetch();
            $this->pointer = 0;
        }
        return $this->current;
    }

    /**
     * @param string $fieldName
     *
     * @return null|string
     */
    public function fetchField(string $fieldName)
    {
        $currentRow = $this->current();
        switch ($this->iteratorFetchType) {
            case $this::ITERATOR_FETCH_ASSOC:
                return array_key_exists($fieldName, $currentRow)
                    ? $currentRow[$fieldName]
                    : null;

            case $this::ITERATOR_FETCH_OBJECT:
                return property_exists($currentRow, $fieldName)
                    ? $currentRow->{$fieldName}
                    : null;
        }

        return null; // @codeCoverageIgnore
    }

    /**
     * @return array|null|\stdClass
     */
    public function fetchRow()
    {
        return $this->current();
    }

    /**
     * Move forward to next element
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->fetch();
    }

    /**
     * Increments the
     */
    private function fetch()
    {
        ++$this->pointer;
        $this->current = $this->statement->fetch($this->getPdoFetchType());
    }

    /**
     * @return int
     */
    private function getPdoFetchType(): int
    {
        switch ($this->iteratorFetchType) {
            case $this::ITERATOR_FETCH_OBJECT:
                return \PDO::FETCH_OBJ;

            default:
                return \PDO::FETCH_ASSOC;
        }
    }

    /**
     * Return the key of the current element
     * @link  http://php.net/manual/en/iterator.key.php
     * @return int scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * Checks if current position is valid
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->pointer >= 0 && $this->pointer < $this->count();
    }

    /**
     * Rewind the Iterator to the first element
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->pointer = 0;
        $this->current = null;
        $this->statement->closeCursor();
        $this->statement->execute();
    }

    /**
     * Count elements of an object
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->statement->rowCount();
    }
}
