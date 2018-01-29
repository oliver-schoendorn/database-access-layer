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

class TableReference extends AbstractReference
{
    /**
     * Returns a sql fragment for using the reference as identifier
     *
     * Since the reference should be used as an identifier, this method
     * MUST return the name of the reference and the alias e.g. "`foo` AS `bar`".
     *
     * @param Specification $specification
     *
     * @return string
     */
    public function toSqlIdentifier(Specification $specification): string
    {
        return $specification->quoteIdentifier($this->getName()) .
               ($this->getAlias() ? ' AS ' . $specification->quoteIdentifier($this->getAlias()) : '');
    }

    /**
     * Returns a sql fragment for referencing a reference
     *
     * Since the reference should be used as a pointer, this method
     * MUST return only the alias (or the name, if no alias is set) e.g. "`bar`".
     *
     * @param Specification $specification
     *
     * @return string
     */
    public function toSqlReference(Specification $specification): string
    {
        return $specification->quoteIdentifier($this->getAliasOrName());
    }
}
