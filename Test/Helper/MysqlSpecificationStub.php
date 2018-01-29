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

namespace OS\DatabaseAccessLayer\Test\Helper;



use OS\DatabaseAccessLayer\Driver;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Specification;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\MockObject;

class MysqlSpecificationStub extends Specification
{
    /**
     * MysqlSpecificationStub constructor.
     *
     * @throws \ReflectionException
     */
    public function __construct()
    {
        /** @var Driver|MockObject $driver */
        $driver = (new Generator())->getMockForAbstractClass(
            Driver::class,
            [],
            '',
            false,
            false
        );

        parent::__construct($driver);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }

}
