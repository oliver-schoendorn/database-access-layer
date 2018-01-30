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

namespace OS\DatabaseAccessLayer\Test\UnitTest\Expression\Condition;


use OS\DatabaseAccessLayer\Expression\Condition\Condition;
use OS\DatabaseAccessLayer\Expression\Condition\ConditionEquals;
use OS\DatabaseAccessLayer\Statement\ParameterContainer;
use OS\DatabaseAccessLayer\Statement\ParameterContainerInterface;
use OS\DatabaseAccessLayer\Test\Helper\MysqlSpecificationStub;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;

class ConditionEqualsTest extends UnitTestCase
{
    /**
     * @throws \OS\DatabaseAccessLayer\Expression\Condition\Exception\InvalidConditionTypeException
     * @throws \ReflectionException
     */
    public function testConstructorWillSetSides()
    {
        $condition = new ConditionEquals('left', 'right', Condition::TYPE_IDENTIFIER, Condition::TYPE_VALUE);
        $inspector = new ObjectInspector($condition);

        verify($inspector->invoke('getPart', [ 0 ]))->equals([
            'left',
            Condition::TYPE_IDENTIFIER
        ]);

        verify($inspector->invoke('getPart', [ 1 ]))->equals([
            'right',
            Condition::TYPE_VALUE
        ]);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Expression\Condition\Exception\InvalidConditionTypeException
     * @throws \ReflectionException
     */
    public function testSetLeft()
    {
        $condition = new ConditionEquals(null, null);
        $condition->setLeft('left', Condition::TYPE_IDENTIFIER);
        $inspector = new ObjectInspector($condition);

        verify($inspector->invoke('getPart', [ 0 ]))->equals([
            'left',
            Condition::TYPE_IDENTIFIER
        ]);

        verify($inspector->invoke('getPart', [ 1 ]))->equals([
            null,
            Condition::TYPE_VALUE
        ]);
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Expression\Condition\Exception\InvalidConditionTypeException
     * @throws \ReflectionException
     */
    public function testSetRight()
    {
        $condition = new ConditionEquals(null, null);
        $condition->setRight('right', Condition::TYPE_IDENTIFIER);
        $inspector = new ObjectInspector($condition);

        verify($inspector->invoke('getPart', [ 0 ]))->equals([
            null,
            Condition::TYPE_VALUE
        ]);

        verify($inspector->invoke('getPart', [ 1 ]))->equals([
            'right',
            Condition::TYPE_IDENTIFIER
        ]);
    }

    public function preparableSqlDataProvider()
    {
        return [
            'two string values' => [
                [ 'left', Condition::TYPE_IDENTIFIER ],
                [ 'right', Condition::TYPE_VALUE ],
                null,
                function($response) {
                    verify($response)->equals('(`left` = \'right\')');
                }
            ],
            'two string values with container' => [
                [ 'left', Condition::TYPE_IDENTIFIER ],
                [ 'right', Condition::TYPE_VALUE ],
                new ParameterContainer(),
                function($response, ParameterContainerInterface $container) {
                    $inspector = new ObjectInspector($container);
                    verify($response)->equals('(`left` = :condition)');
                    verify($inspector->getValue('parameters'))->hasKey('condition');
                    verify($inspector->getValue('values'))->hasKey('condition');
                }
            ]
        ];
    }

    /**
     * @param array                            $left
     * @param array                            $right
     * @param ParameterContainerInterface|null $container
     * @param callable                         $verify
     *
     * @throws \OS\DatabaseAccessLayer\Expression\Condition\Exception\InvalidConditionTypeException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterKeyException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException
     * @throws \ReflectionException
     *
     * @dataProvider preparableSqlDataProvider
     */
    public function testToPreparableSql(
        array $left,
        array $right,
        ParameterContainerInterface $container = null,
        callable $verify
    )
    {
        $spec = new MysqlSpecificationStub();
        $condition = new ConditionEquals(null, null);
        $condition->setLeft(...$left);
        $condition->setRight(...$right);

        $response = $condition->toPreparableSql($spec,$container);

        if ($container) {
            $verify($response, $container);
        }
        else {
            $verify($response);
        }
    }
}
