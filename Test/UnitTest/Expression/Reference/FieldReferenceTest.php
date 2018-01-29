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

namespace OS\DatabaseAccessLayer\Test\UnitTest\Expression\Reference;


use OS\DatabaseAccessLayer\Expression\Reference\FieldReference;
use OS\DatabaseAccessLayer\Expression\Reference\TableReference;
use OS\DatabaseAccessLayer\Specification;
use OS\DatabaseAccessLayer\Test\Helper\MysqlSpecificationStub;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;

class FieldReferenceTest extends UnitTestCase
{
    /**
     * @var Specification
     */
    private $specification;

    /**
     * @throws \ReflectionException
     */
    protected function setUp()
    {
        $this->specification = new MysqlSpecificationStub();
        parent::setUp();
    }

    public function testToSql()
    {
        $reference = new FieldReference('field');
        $sqlFragment = $reference->toSql($this->specification);
        verify($sqlFragment)->equals('`field`');

        $reference = new FieldReference('field', 'alias');
        $sqlFragment = $reference->toSql($this->specification);
        verify($sqlFragment)->equals('`field` AS `alias`');

        $reference = new FieldReference('field', 'alias', new TableReference('table'));
        $sqlFragment = $reference->toSql($this->specification);
        verify($sqlFragment)->equals('`table`.`field` AS `alias`');

        $reference = new FieldReference('field', 'alias', new TableReference('table', 'tableAlias'));
        $sqlFragment = $reference->toSql($this->specification);
        verify($sqlFragment)->equals('`tableAlias`.`field` AS `alias`');


        $reference = new FieldReference('field');
        $sqlFragment = $reference->toSql($this->specification, true);
        verify($sqlFragment)->equals('`field`');

        $reference = new FieldReference('field', 'alias');
        $sqlFragment = $reference->toSql($this->specification, true);
        verify($sqlFragment)->equals('`alias`');

        $reference = new FieldReference('field', 'alias', new TableReference('table'));
        $sqlFragment = $reference->toSql($this->specification, true);
        verify($sqlFragment)->equals('`table`.`alias`');

        $reference = new FieldReference('field', null, new TableReference('table', 'tableAlias'));
        $sqlFragment = $reference->toSql($this->specification, true);
        verify($sqlFragment)->equals('`tableAlias`.`field`');

        $reference = new FieldReference('field', 'alias', new TableReference('table', 'tableAlias'));
        $sqlFragment = $reference->toSql($this->specification, true);
        verify($sqlFragment)->equals('`tableAlias`.`alias`');
    }

    public function testToSqlIdentifier()
    {
        $reference = new FieldReference('field');
        $sqlFragment = $reference->toSqlIdentifier($this->specification);
        verify($sqlFragment)->equals('`field`');

        $reference = new FieldReference('field', 'alias');
        $sqlFragment = $reference->toSqlIdentifier($this->specification);
        verify($sqlFragment)->equals('`field` AS `alias`');

        $reference = new FieldReference('field', 'alias', new TableReference('table'));
        $sqlFragment = $reference->toSqlIdentifier($this->specification);
        verify($sqlFragment)->equals('`table`.`field` AS `alias`');

        $reference = new FieldReference('field', 'alias', new TableReference('table', 'tableAlias'));
        $sqlFragment = $reference->toSqlIdentifier($this->specification);
        verify($sqlFragment)->equals('`tableAlias`.`field` AS `alias`');

        $reference = new FieldReference('field', 'alias', new TableReference('table'));
        $sqlFragment = $reference->toSqlIdentifier($this->specification, false);
        verify($sqlFragment)->equals('`table`.`field` AS `alias`');

        $reference = new FieldReference('field', 'alias', new TableReference('table', 'tableAlias'));
        $sqlFragment = $reference->toSqlIdentifier($this->specification, false);
        verify($sqlFragment)->equals('`table`.`field` AS `alias`');
    }

    public function testToSqlReference()
    {
        $reference = new FieldReference('field');
        $sqlFragment = $reference->toSqlReference($this->specification);
        verify($sqlFragment)->equals('`field`');

        $reference = new FieldReference('field', 'alias');
        $sqlFragment = $reference->toSqlReference($this->specification);
        verify($sqlFragment)->equals('`alias`');

        $reference = new FieldReference('field', 'alias', new TableReference('table'));
        $sqlFragment = $reference->toSqlReference($this->specification);
        verify($sqlFragment)->equals('`table`.`alias`');

        $reference = new FieldReference('field', null, new TableReference('table', 'tableAlias'));
        $sqlFragment = $reference->toSqlReference($this->specification);
        verify($sqlFragment)->equals('`tableAlias`.`field`');

        $reference = new FieldReference('field', 'alias', new TableReference('table', 'tableAlias'));
        $sqlFragment = $reference->toSqlReference($this->specification);
        verify($sqlFragment)->equals('`tableAlias`.`alias`');
    }
}
