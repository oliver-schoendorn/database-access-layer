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

namespace OS\DatabaseAccessLayer\Statement;


use OS\DatabaseAccessLayer\Driver;
use OS\DatabaseAccessLayer\Expression\PreparableExpression;
use OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;

interface PreparableStatement extends PreparableExpression
{
    /**
     * @param Driver $driver
     * @param ParameterContainerInterface $parameterContainer
     *
     * @return PreparedStatement
     * @throws StatementPreparationException
     */
    public function prepare(Driver $driver, ParameterContainerInterface $parameterContainer): PreparedStatement;

    /**
     * @param Driver $driver
     *
     * @return PreparedStatement
     * @throws StatementPreparationException
     * @throws StatementExecutionException
     */
    public function execute(Driver $driver);
}
