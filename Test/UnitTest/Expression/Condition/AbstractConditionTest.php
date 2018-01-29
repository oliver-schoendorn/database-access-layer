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


use OS\DatabaseAccessLayer\Expression\Condition\AbstractCondition;
use OS\DatabaseAccessLayer\Expression\Condition\Condition;
use OS\DatabaseAccessLayer\Expression\Condition\Exception\InvalidConditionTypeException;
use OS\DatabaseAccessLayer\Expression\Expression;
use OS\DatabaseAccessLayer\Expression\PreparableExpression;
use OS\DatabaseAccessLayer\Expression\Reference\FieldReference;
use OS\DatabaseAccessLayer\Expression\Reference\TableReference;
use OS\DatabaseAccessLayer\Specification;
use OS\DatabaseAccessLayer\Statement\ParameterContainer;
use OS\DatabaseAccessLayer\Statement\ParameterContainerInterface;
use OS\DatabaseAccessLayer\Statement\PreparableStatement;
use OS\DatabaseAccessLayer\Test\Helper\MysqlSpecificationStub;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AbstractConditionTest extends UnitTestCase
{
    /**
     * @param string|string[] $mockedMethods
     *
     * @return AbstractCondition|MockObject
     */
    private function getSubject($mockedMethods = []): AbstractCondition
    {
        if ( ! is_array($mockedMethods)) {
            $mockedMethods = [$mockedMethods];
        }

        return $this->getMockForAbstractClass(
            AbstractCondition::class,
            [],
            '',
            false,
            true,
            true,
            $mockedMethods
        );
    }

    /**
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterKeyException
     * @throws \OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException
     * @throws \ReflectionException
     */
    public function testToSql()
    {
        $specification = new MysqlSpecificationStub();

        $subject = $this->getSubject(['toPreparableSql']);
        $subject->expects($this::once())
            ->method('toPreparableSql')
            ->with($specification)
            ->willReturn('Foo Bar');

        $response = $subject->toSql($specification);
        verify($response)->equals('Foo Bar');
    }

    public function partTypeDataProvider()
    {
        return [
            'value' => [ Condition::TYPE_VALUE ],
            'identifier' => [ Condition::TYPE_IDENTIFIER ],
            'literal' => [ Condition::TYPE_LITERAL ],
            'select' => [ Condition::TYPE_SELECT ]
        ];
    }

    /**
     * @param int $type
     *
     * @throws \ReflectionException
     *
     * @dataProvider partTypeDataProvider
     */
    public function testSetPartWithValidValues(int $type)
    {
        $subject = $this->getSubject();
        $inspector = new ObjectInspector($subject);

        $inspector->invoke('setPart', [ 0, 'value' , $type ]);
        verify($inspector->invoke('getPart', [ 0 ]))->equals([
            'value', $type
        ]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetPartWithInvalidValue()
    {
        $this->expectException(InvalidConditionTypeException::class);

        $subject = $this->getSubject();
        $inspector = new ObjectInspector($subject);

        $inspector->invoke('setPart', [ 0, 'value' , -10 ]);
    }

    public function partValueDataProvider()
    {
        return [
            'select' => [
                $this->getMockForAbstractClass(PreparableStatement::class),
                Condition::TYPE_SELECT
            ],
            'reference - field' => [
                new FieldReference('foo'),
                Condition::TYPE_IDENTIFIER
            ],
            'value - table ref' => [
                new TableReference('test'),
                Condition::TYPE_VALUE
            ],
            'value - expression' => [
                $this->getMockForAbstractClass(Expression::class),
                Condition::TYPE_VALUE
            ],
            'value - preparable expression' => [
                $this->getMockForAbstractClass(PreparableExpression::class),
                Condition::TYPE_VALUE
            ],
            'value - string' => [
                'foo',
                Condition::TYPE_VALUE
            ],
            'value - int' => [
                2,
                Condition::TYPE_VALUE
            ],
            'value - bool' => [
                false,
                Condition::TYPE_VALUE
            ],
            'value - null' => [
                null,
                Condition::TYPE_VALUE
            ]
        ];
    }

    /**
     * @param mixed $value
     * @param int $expectedType
     *
     * @throws \ReflectionException
     *
     * @dataProvider partValueDataProvider
     */
    public function testGuessPartType($value, int $expectedType)
    {
        $subject = $this->getSubject();
        $inspector = new ObjectInspector($subject);

        $inspector->invoke('setPart', [ 0, $value ]);
        verify($inspector->getValue('partValues')[0])->equals($value);
        verify($inspector->getValue('partTypes')[0])->equals($expectedType);
    }

    public function partToPreparableSqlValueDataProvider()
    {
        $preparableExpressionWithContainer = $this->getMockBuilder(PreparableExpression::class)
            ->setMethods([ 'toPreparableSql' ])
            ->getMockForAbstractClass();

        $preparableExpressionWithContainer->expects($this::once())
            ->method('toPreparableSql')
            ->willReturnCallback(function($specification, $container) {
                verify($specification)->isInstanceOf(Specification::class);
                verify($container)->isInstanceOf(ParameterContainerInterface::class);

                /** @var ParameterContainerInterface $container */
                $container->addParameter('test', $container::TYPE_INT, 23);
                return '`bar` = :test';
            });

        $preparableExpressionWithoutContainer = $this->getMockBuilder(PreparableExpression::class)
            ->setMethods([ 'toSql' ])
            ->getMockForAbstractClass();

        $preparableExpressionWithoutContainer->expects($this::once())
            ->method('toSql')
            ->willReturnCallback(function($specification) {
                verify($specification)->isInstanceOf(Specification::class);
                return '`bar` = 23';
            });

        $expression = $this->getMockBuilder(Expression::class)
            ->setMethods([ 'toSql' ])
            ->getMockForAbstractClass();

        $expression->expects($this::once())
            ->method('toSql')
            ->willReturn('TEST');

        return [
            'string' => [
                'foo',
                function($result, ParameterContainer $container = null) {
                    verify($result)->equals('\'foo\'');
                }
            ],
            'string with container' => [
                'foo',
                function($result, ParameterContainer $container) {
                    verify($result)->equals(':condition');
                    $inspector = new ObjectInspector($container);
                    verify($inspector->getValue('parameters')['condition'])->equals([
                        ':condition', $container::TYPE_STRING
                    ]);
                },
                true
            ],
            'null' => [
                null,
                function($result, ParameterContainer $container = null) {
                    verify($result)->equals('NULL');
                }
            ],
            'bool - false' => [
                false,
                function($result, ParameterContainer $container = null) {
                    verify($result)->equals('FALSE');
                }
            ],
            'bool - true' => [
                true,
                function($result, ParameterContainer $container = null) {
                    verify($result)->equals('TRUE');
                }
            ],
            'preparable expression with container' => [
                $preparableExpressionWithContainer,
                function($result, ParameterContainer $container) {
                    verify($result)->equals('`bar` = :test');
                    $inspector = new ObjectInspector($container);
                    verify($inspector->getValue('parameters')['test'])->equals([
                        ':test', $container::TYPE_INT
                    ]);
                },
                true
            ],
            'preparable expression without container' => [
                $preparableExpressionWithoutContainer,
                function($result, ParameterContainer $container = null) {
                    verify($result)->equals('`bar` = 23');
                }
            ],
            'expression' => [
                $expression,
                function($result, ParameterContainer $container = null) {
                    verify($result)->equals('TEST');
                }
            ]
        ];
    }

    /**
     * @param mixed $value
     * @param callable $verify
     * @param bool     $useContainer
     *
     * @throws \ReflectionException
     *
     * @dataProvider partToPreparableSqlValueDataProvider
     */
    public function testPartToPreparableSqlWithValues($value, callable $verify, bool $useContainer = false)
    {
        $specification = new MysqlSpecificationStub();

        $subject = $this->getSubject();
        $inspector = new ObjectInspector($subject);

        $container = $useContainer ? new ParameterContainer() : null;
        $response = $inspector->invoke('partToPreparableSql', [
            $specification, $container, $value, Condition::TYPE_VALUE ]);

        $verify($response, $container);
    }

    public function partToPreparableSqlIdentifierDataProvider()
    {
        $expression = $this->getMockBuilder(Expression::class)
            ->setMethods([ 'toSql' ])
            ->getMockForAbstractClass();

        $expression->expects($this::once())
            ->method('toSql')
            ->willReturn('`bar` = \'foo\' /* test */');

        return [
            'field ref' => [
                new FieldReference('foo'),
                function($response) {
                    verify($response)->equals('`foo`');
                }
            ],
            'field ref with alias' => [
                new FieldReference('foo', 'bar'),
                function($response) {
                    verify($response)->equals('`bar`');
                }
            ],
            'field ref with table' => [
                new FieldReference('foo', 'bar', new TableReference('test')),
                function($response) {
                    verify($response)->equals('`test`.`bar`');
                }
            ],
            'field ref with table and table alias' => [
                new FieldReference('foo', null, new TableReference('test', 'tableAlias')),
                function($response) {
                    verify($response)->equals('`tableAlias`.`foo`');
                }
            ],
            'field ref with alias, table and table alias' => [
                new FieldReference('foo', 'alias', new TableReference('test', 'tableAlias')),
                function($response) {
                    verify($response)->equals('`tableAlias`.`alias`');
                }
            ],
            'table ref' => [
                new TableReference('foo'),
                function($response) {
                    verify($response)->equals('`foo`');
                }
            ],
            'table ref with alias' => [
                new TableReference('foo', 'bar'),
                function($response) {
                    verify($response)->equals('`bar`');
                }
            ],
            'expression' => [
                $expression,
                function($response) {
                    verify($response)->equals('`bar` = \'foo\' /* test */');
                }
            ],
            'string' => [
                'test',
                function($response) {
                    verify($response)->equals('`test`');
                }
            ],
            'string with chained ids' => [
                'test.foo.bar',
                function($response) {
                    verify($response)->equals('`test`.`foo`.`bar`');
                }
            ]
        ];
    }

    /**
     * @param mixed $value
     * @param callable $verify
     * @param bool     $useContainer
     *
     * @throws \ReflectionException
     *
     * @dataProvider partToPreparableSqlIdentifierDataProvider
     */
    public function testPartToPreparableSqlWithIdentifier($value, callable $verify, bool $useContainer = false)
    {
        $specification = new MysqlSpecificationStub();

        $subject = $this->getSubject();
        $inspector = new ObjectInspector($subject);

        $container = $useContainer ? new ParameterContainer() : null;
        $response = $inspector->invoke('partToPreparableSql', [
            $specification, $container, $value, Condition::TYPE_IDENTIFIER ]);

        $verify($response, $container);
    }

    public function partToPreparableSqlSelectDataProvider()
    {

        $preparableExpressionWithContainer = $this->getMockBuilder(PreparableExpression::class)
            ->setMethods([ 'toPreparableSql' ])
            ->getMockForAbstractClass();

        $preparableExpressionWithContainer->expects($this::once())
            ->method('toPreparableSql')
            ->willReturnCallback(function($specification, $container) {
                verify($specification)->isInstanceOf(Specification::class);
                verify($container)->isInstanceOf(ParameterContainerInterface::class);

                /** @var ParameterContainerInterface $container */
                $container->addParameter('test', $container::TYPE_INT, 23);
                return '`bar` = :test';
            });

        $preparableExpressionWithoutContainer = $this->getMockBuilder(PreparableExpression::class)
            ->setMethods([ 'toSql' ])
            ->getMockForAbstractClass();

        $preparableExpressionWithoutContainer->expects($this::once())
            ->method('toSql')
            ->willReturnCallback(function($specification) {
                verify($specification)->isInstanceOf(Specification::class);
                return '`bar` = 23';
            });

        $expression = $this->getMockBuilder(Expression::class)
            ->setMethods([ 'toSql' ])
            ->getMockForAbstractClass();

        $expression->expects($this::once())
            ->method('toSql')
            ->willReturn('TEST');

        return [
            'literal' => [
                'SELECT * FROM nowhere',
                function ($response, ParameterContainer $container = null) {
                    verify($response)->equals('SELECT * FROM nowhere');
                }
            ],
            'literal - int' => [
                123,
                function ($response, ParameterContainer $container = null) {
                    verify($response)->equals('123');
                }
            ],
            'preparable expression' => [
                $preparableExpressionWithoutContainer,
                function($response, ParameterContainer $container = null) {
                    verify($response)->equals('`bar` = 23');
                }
            ],
            'preparable expression with container' => [
                $preparableExpressionWithContainer,
                function($response, ParameterContainer $container) {
                    verify($response)->equals('`bar` = :test');
                    $inspector = new ObjectInspector($container);

                    verify($inspector->getValue('parameters'))->hasKey('test');
                    verify($inspector->getValue('parameters')['test'])->equals([
                        ':test', ParameterContainer::TYPE_INT
                    ]);


                    verify($inspector->getValue('values'))->hasKey('test');
                    verify($inspector->getValue('values')['test'])->equals(23);

                },
                true
            ],
            'expression' => [
                $expression,
                function($response, ParameterContainer $container = null) {
                    verify($response)->equals('TEST');
                }
            ],
        ];
    }

    /**
     * @param mixed $value
     * @param callable $verify
     * @param bool     $useContainer
     *
     * @throws \ReflectionException
     *
     * @dataProvider partToPreparableSqlSelectDataProvider
     */
    public function testPartToPreparableSqlWithSelect($value, callable $verify, bool $useContainer = false)
    {
        $specification = new MysqlSpecificationStub();

        $subject = $this->getSubject();
        $inspector = new ObjectInspector($subject);

        $container = $useContainer ? new ParameterContainer() : null;
        $response = $inspector->invoke('partToPreparableSql', [
            $specification, $container, $value, Condition::TYPE_SELECT ]);

        $verify($response, $container);
    }

    public function partToPreparableSqlLiteralDataProvider()
    {
        $expression = $this->getMockBuilder(Expression::class)
            ->setMethods([ 'toSql' ])
            ->getMockForAbstractClass();

        $expression->expects($this::once())
            ->method('toSql')
            ->willReturn('`bar` = \'foo\' /* test */');

        return [
            'string' => [
                'foo', function($response) {
                    verify($response)->equals('foo');
                }
            ],
            'int' => [
                123, function($response) {
                    verify($response)->equals(123);
                }
            ],
            'null' => [
                null, function($response) {
                    verify($response)->equals('NULL');
                }
            ],
            'bool - false' => [
                false, function($response) {
                    verify($response)->equals('FALSE');
                }
            ],
            'bool - true' => [
                true, function($response) {
                    verify($response)->equals('TRUE');
                }
            ],
            'expression' => [
                $expression, function($response) {
                    verify($response)->equals('`bar` = \'foo\' /* test */');
                }
            ]
        ];
    }

    /**
     * @param mixed $value
     * @param callable $verify
     *
     * @throws \ReflectionException
     *
     * @dataProvider partToPreparableSqlLiteralDataProvider
     */
    public function testPartToPreparableSqlWithLiterals($value, callable $verify)
    {
        $specification = new MysqlSpecificationStub();

        $subject = $this->getSubject();
        $inspector = new ObjectInspector($subject);

        $response = $inspector->invoke('partToPreparableSql', [
            $specification, null, $value, Condition::TYPE_LITERAL ]);

        $verify($response);
    }

    /**
     * @throws \ReflectionException
     */
    public function testPartToSql()
    {
        $spec = new MysqlSpecificationStub();
        $subject = $this->getSubject('partToPreparableSql');
        $subject->expects($this::once())
            ->method('partToPreparableSql')
            ->with($spec, null, 'test', Condition::TYPE_VALUE)
            ->willReturn('test');

        $response = (new ObjectInspector($subject))->invoke(
            'partToSql', [ $spec, 'test', Condition::TYPE_VALUE ]);
        verify($response)->equals('test');
    }
}
