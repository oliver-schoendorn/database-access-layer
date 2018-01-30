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

namespace OS\DatabaseAccessLayer\Expression\Condition;


use OS\DatabaseAccessLayer\Specification;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterKeyException;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException;
use OS\DatabaseAccessLayer\Statement\ParameterContainerInterface;

class ConditionEquals extends AbstractCondition
{
    /**
     * @param mixed $leftValue
     * @param mixed $rightValue
     * @param int|null $leftType
     * @param int|null $rightType
     *
     * @throws Exception\InvalidConditionTypeException
     */
    public function __construct($leftValue, $rightValue, int $leftType = null, int $rightType = null)
    {
        $this->setLeft($leftValue, $leftType);
        $this->setRight($rightValue, $rightType);
    }

    /**
     * @param mixed $value
     * @param int|null $type
     *
     * @return static
     * @throws Exception\InvalidConditionTypeException
     */
    public function setLeft($value, int $type = null)
    {
        return $this->setPart(0, $value, $type);
    }

    /**
     * @param mixed $value
     * @param int|null $type
     *
     * @return static
     * @throws Exception\InvalidConditionTypeException
     */
    public function setRight($value, int $type = null)
    {
        return $this->setPart(1, $value, $type);
    }

    /**
     * @param Specification $specification
     * @param ParameterContainerInterface $container
     *
     * @return string
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    public function toPreparableSql(
        Specification $specification,
        ParameterContainerInterface &$container = null
    ): string
    {
        return '(' . $this->partToPreparableSql($specification, $container, ...$this->getPart(0)) .
               ' = ' .
               $this->partToPreparableSql($specification, $container, ...$this->getPart(1)) . ')';
    }
}
