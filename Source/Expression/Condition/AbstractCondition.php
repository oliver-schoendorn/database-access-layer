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


use OS\DatabaseAccessLayer\Expression\Condition\Exception\InvalidConditionTypeException;
use OS\DatabaseAccessLayer\Expression\Expression;
use OS\DatabaseAccessLayer\Expression\PreparableExpression;
use OS\DatabaseAccessLayer\Expression\Reference\FieldReference;
use OS\DatabaseAccessLayer\Expression\Reference\Reference;
use OS\DatabaseAccessLayer\Specification;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterKeyException;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException;
use OS\DatabaseAccessLayer\Statement\ParameterContainerInterface;
use OS\DatabaseAccessLayer\Statement\PreparableStatement;

abstract class AbstractCondition implements Condition
{
    /**
     * @var mixed[]
     */
    private $partValues = [];

    /**
     * @var int[]|null[]
     */
    private $partTypes = [];

    /**
     * @param Specification $specification
     *
     * @return string
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    public function toSql(Specification $specification): string
    {
        return $this->toPreparableSql($specification);
    }

    /**
     * @param Specification $specification
     * @param ParameterContainerInterface $container
     *
     * @return string
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    abstract public function toPreparableSql(
        Specification $specification,
        ParameterContainerInterface &$container = null
    ): string;

    /**
     * @param int $part
     * @param mixed $value
     * @param int|null $type
     *
     * @return static
     * @throws InvalidConditionTypeException
     */
    protected function setPart(int $part, $value, int $type = null)
    {
        $type = $type ?? $this->guessType($value);
        $this->validateType($type);

        $this->partValues[$part] = $value;
        $this->partTypes[$part] = $type;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    protected function guessType($value): int
    {
        switch (true) {
            case $value instanceof FieldReference:
                return $this::TYPE_IDENTIFIER;

            case $value instanceof PreparableStatement:
                return $this::TYPE_SELECT;

            default:
                return $this::TYPE_VALUE;
        }
    }

    /**
     * @param int $type
     *
     * @return int
     * @throws InvalidConditionTypeException
     */
    protected function validateType(int $type): int
    {
        if ( ! in_array($type, [
            $this::TYPE_VALUE,
            $this::TYPE_IDENTIFIER,
            $this::TYPE_LITERAL,
            $this::TYPE_SELECT
        ])) {
            throw new InvalidConditionTypeException();
        }

        return $type;
    }

    /**
     * @param int $part
     *
     * @return array
     */
    protected function getPart(int $part): array
    {
        return [
            $this->partValues[$part] ?? '',
            $this->partTypes[$part] ?? $this::TYPE_VALUE
        ];
    }

    /**
     * @param Specification $specification
     * @param mixed $value
     * @param ParameterContainerInterface|null $parameterContainer
     * @param int $valueType
     *
     * @return string
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    protected function partToPreparableSql(
        Specification $specification,
        ParameterContainerInterface $parameterContainer = null,
        $value,
        int $valueType
    ): string
    {
        switch ($valueType) {
            case $this::TYPE_VALUE:
                $value = $this->valueToSql($specification, $value, $parameterContainer);
                break;

            case $this::TYPE_IDENTIFIER:
                $value = $this->identifierToSql($specification, $value);
                break;

            case $this::TYPE_SELECT:
                $value = $this->selectToSql($specification, $value, $parameterContainer);
                break;

            case $this::TYPE_LITERAL:
                $value = $this->literalToSql($specification, $value);
                break;
        }

        return $value;
    }

    /**
     * @param Specification $specification
     * @param mixed $value
     * @param int $valueType
     *
     * @return string
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    protected function partToSql(Specification $specification, $value, int $valueType): string
    {
        return $this->partToPreparableSql($specification, null, $value, $valueType);
    }

    /**
     * @param Specification $specification
     * @param mixed $value
     * @param ParameterContainerInterface|null $container
     *
     * @return string
     * @throws InvalidParameterKeyException
     * @throws InvalidParameterTypeException
     */
    private function valueToSql(Specification $specification, $value, ParameterContainerInterface $container = null): string
    {
        if ($value instanceof PreparableExpression) {
            return $container
                ? $value->toPreparableSql($specification, $container)
                : $value->toSql($specification);
        }

        if ($value instanceof Expression) {
            return $value->toSql($specification);
        }

        if ($container) {
            return ':' . $container->addParameter('condition', $container::guessParameterType($value), $value);
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return $specification->quoteValue($value);
    }

    /**
     * @param Specification $specification
     * @param mixed $value
     *
     * @return string
     */
    private function identifierToSql(Specification $specification, $value): string
    {
        if ($value instanceof Reference) {
            return $value->toSqlReference($specification);
        }

        if ($value instanceof Expression) {
            return $value->toSql($specification);
        }

        return $specification->quoteIdentifierChain($value);
    }

    /**
     * @param Specification $specification
     * @param mixed $value
     *
     * @return string
     */
    private function literalToSql(Specification $specification, $value): string
    {
        if ($value instanceof Expression) {
            $value = $value->toSql($specification);
        }

        if (is_null($value)) {
            $value = 'NULL';
        }

        if (is_bool($value)) {
            $value = $value ? 'TRUE' : 'FALSE';
        }

        return (string) $value;
    }

    /**
     * @param Specification $specification
     * @param mixed $value
     * @param ParameterContainerInterface|null $container
     *
     * @return string
     */
    private function selectToSql(Specification $specification, $value, ParameterContainerInterface $container = null): string
    {
        if ($value instanceof PreparableExpression) {
            return $container
                ? $value->toPreparableSql($specification, $container)
                : $value->toSql($specification);
        }

        if ($value instanceof Expression) {
            return $value->toSql($specification);
        }

        return (string) $value;
    }
}
