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

abstract class AbstractReference implements Reference
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var null|string
     */
    private $alias;

    /**
     * AbstractReference constructor.
     *
     * @param string      $name
     * @param string|null $alias
     */
    public function __construct(string $name, string $alias = null)
    {
        $this->name = $name;
        $this->alias = $alias;
    }

    /**
     * Returns the name of the reference
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the alias or null, if no alias is set
     *
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Returns either the alias or the name, if no alias is set
     *
     * @return string
     */
    public function getAliasOrName(): string
    {
        return $this->alias ?? $this->name;
    }

    /**
     * @param Specification $specification
     * @param bool          $useAlias
     *
     * @return string
     */
    public function toSql(Specification $specification, bool $useAlias = false): string
    {
        return $useAlias ? $this->toSqlReference($specification) : $this->toSqlIdentifier($specification);
    }
}
