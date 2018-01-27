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


use OS\DatabaseAccessLayer\Statement\Exception\InvalidFetchTypeException;

interface Result extends \Iterator, \Countable
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
