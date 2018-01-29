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


use OS\DatabaseAccessLayer\Expression\Reference\AbstractReference;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;

class AbstractReferenceTest extends UnitTestCase
{
    /**
     * @param array $arguments
     *
     * @return AbstractReference|object
     */
    private function getSubject(array $arguments): AbstractReference
    {
        return $this->getMockForAbstractClass(AbstractReference::class, $arguments);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructor()
    {
        $reference = $this->getSubject([ 'testName', 'testAlias' ]);
        $inspector = new ObjectInspector($reference);

        verify($inspector->getValue('name'))->equals('testName');
        verify($inspector->getValue('alias'))->equals('testAlias');
    }

    public function testGetters()
    {
        $reference = $this->getSubject([ 'aName', 'anAlias' ]);
        $inspector = new ObjectInspector($reference);
        $inspector->setValue('name', 'testName');
        $inspector->setValue('alias', 'testAlias');

        verify($reference->getName())->equals('testName');
        verify($reference->getAlias())->equals('testAlias');
    }

    public function testGetAliasOrName()
    {
        $reference = $this->getSubject([ 'aName' ]);
        verify($reference->getAliasOrName())->equals('aName');

        $reference = $this->getSubject([ 'aName', 'anAlias' ]);
        verify($reference->getAliasOrName())->equals('anAlias');

        $inspector = new ObjectInspector($reference);
        $inspector->setValue('alias', 'testAlias');
        verify($reference->getAliasOrName())->equals('testAlias');

        $inspector->setValue('alias', null);
        $inspector->setValue('name', 'testName');
        verify($reference->getAliasOrName())->equals('testName');
    }
}
