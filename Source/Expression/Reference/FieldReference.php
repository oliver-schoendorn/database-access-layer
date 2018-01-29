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

namespace OS\DatabaseAccessLayer\Expression\Reference;


use OS\DatabaseAccessLayer\Specification;

class FieldReference extends AbstractReference
{
    /**
     * @var TableReference|null
     */
    private $table;

    /**
     * FieldReference constructor.
     *
     * @param string              $name
     * @param string|null         $alias
     * @param TableReference|null $table
     */
    public function __construct(string $name, string $alias = null, TableReference $table = null)
    {
        $this->table = $table;
        parent::__construct($name, $alias);
    }

    /**
     * Returns a sql fragment for using the reference as identifier
     *
     * Since the reference should be used as an identifier, this method
     * MUST return the name of the reference and the alias e.g. "`foo` AS `bar`".
     *
     * @param Specification $specification
     * @param bool $useTableAlias
     *
     * @return string
     */
    public function toSqlIdentifier(Specification $specification, bool $useTableAlias = true): string
    {
        $identifiers = [ $this->getName() ];

        if ($this->table) {
            array_unshift($identifiers, $useTableAlias
                ? $this->table->getAliasOrName()
                : $this->table->getName());
        }

        return $specification->quoteIdentifierChain($identifiers) .
               ($this->getAlias() ? ' AS ' . $specification->quoteIdentifier($this->getAlias()) : '');
    }

    /**
     * Returns a sql fragment for referencing a reference
     *
     * Since the reference should be used as a pointer, this method
     * MUST return only the alias (or the name, if no alias is set) e.g. "`bar`".
     *
     * @param Specification $specification
     * @param bool $useTableAlias
     *
     * @return string
     */
    public function toSqlReference(Specification $specification, bool $useTableAlias = true): string
    {
        $identifiers = [ $this->getAliasOrName() ];

        if ($this->table) {
            array_unshift($identifiers, $useTableAlias
                ? $this->table->getAliasOrName()
                : $this->table->getName());
        }

        return $specification->quoteIdentifierChain($identifiers);
    }
}
