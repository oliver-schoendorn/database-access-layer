<?php

namespace OS\DatabaseAccessLayer\Statement;


use OS\DatabaseAccessLayer\Statement\Exception\InvalidFetchTypeException;

interface Result extends \SeekableIterator, \Countable
{
    /**
     * Available fetch types
     */
    const ITERATOR_FETCH_ASSOC = 0;
    const ITERATOR_FETCH_OBJECT = 1;

    /**
     * Defines the return type of the @see Statement::current() method
     *
     * Use the ITERATOR_FETCH_* constants as argument. Method must validate the given
     * iterator fetch type, as all other methods rely on this variable to be valid.
     *
     * @param int $iteratorFetchType
     *
     * @return static
     * @throws InvalidFetchTypeException
     */
    public function setIteratorFetchType(int $iteratorFetchType);

    /**
     * @return int
     */
    public function getIteratorFetchType(): int;

    /**
     * @param string $fieldName
     *
     * @return string|null
     */
    public function fetchField(string $fieldName);

    /**
     * @return array|\stdClass
     */
    public function fetchRow();
}
