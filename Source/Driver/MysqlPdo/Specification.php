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

namespace OS\DatabaseAccessLayer\Driver\MysqlPdo;

use OS\DatabaseAccessLayer\Driver;
use OS\DatabaseAccessLayer\Expression\Expression;
use OS\DatabaseAccessLayer\Specification as SpecificationInterface;


class Specification implements SpecificationInterface
{
    /**
     * @var Driver
     */
    private $driver;

    /**
     * Specification constructor.
     *
     * @param Driver $driver
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return string
     */
    public function getIdentifierQuote(): string
    {
        return '`';
    }

    /**
     * @return string
     */
    public function getIdentifierSeparator(): string
    {
        return '.';
    }

    /**
     * @return string
     */
    public function getValueQuote(): string
    {
        return '\'';
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return $identifier;
        }

        return $this->getIdentifierQuote() . $identifier . $this->getIdentifierQuote();
    }

    /**
     * @param string|string[] $identifierChain
     *
     * @return string
     */
    public function quoteIdentifierChain($identifierChain): string
    {
        if ( ! is_array($identifierChain)) {
            $identifierChain = explode($this->getIdentifierSeparator(), $identifierChain);
        }

        return implode($this->getIdentifierSeparator(), array_map([ $this, 'quoteIdentifier' ], $identifierChain));
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function quoteValue($value): string
    {
        // Stringify expression (handles quoting on its own)
        if ($value instanceof Expression) {
            return $value->toSql($this);
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        // Stringify resource
        if (is_resource($value)) {
            rewind($value);
            $value = stream_get_contents($value);
        }

        return $this->driver->escape($value);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function quoteTrustedValue($value): string
    {
        return $this->getValueQuote() . $value . $this->getValueQuote();
    }
}
