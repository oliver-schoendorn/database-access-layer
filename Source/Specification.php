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


interface Specification
{
    /**
     * @return string
     */
    public function getIdentifierQuote(): string;

    /**
     * @return string
     */
    public function getIdentifierSeparator(): string;

    /**
     * @return string
     */
    public function getValueQuote(): string;

    /**
     * @param string $identifier
     *
     * @return string
     */
    public function quoteIdentifier(string $identifier): string;

    /**
     * @param string|string[] $identifierChain
     *
     * @return string
     */
    public function quoteIdentifierChain($identifierChain): string;

    /**
     * @param $value
     *
     * @return string
     */
    public function quoteValue($value): string;

    /**
     * @param $value
     *
     * @return string
     */
    public function quoteTrustedValue($value): string;
}