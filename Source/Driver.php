<?php
/**
 * Copyright (c) 2017 Oliver Schöndorn, Markus Schmidt
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

namespace OS\DatabaseAccessLayer;


use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException;
use OS\DatabaseAccessLayer\Statement\PreparedStatement;
use OS\DatabaseAccessLayer\Statement\Result;

interface Driver
{
    /**
     * Returns the adapter specification.
     *
     * @return Specification
     */
    public function getSpecification(): Specification;

    /**
     * Escapes a given value and quotes it according the used driver and specification.
     *
     * This method MUST use the specification quote method, which SHOULD use the adapter specific method
     * for quoting values (\PDO::quote) for example.
     *
     * @param string $value
     *
     * @return string
     */
    public function escape(string $value): string;

    /**
     * Returns the id of the last inserted row
     *
     * @return int
     */
    public function getLastInsertedId(): int;

    /**
     * @param string $sql
     *
     * @return Result
     * @throws StatementExecutionException
     */
    public function query(string $sql): Result;

    /**
     * @param string $sql
     *
     * @return PreparedStatement
     * @throws StatementPreparationException
     */
    public function prepare(string $sql): PreparedStatement;
}
