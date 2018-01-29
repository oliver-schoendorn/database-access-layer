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

namespace OS\DatabaseAccessLayer\Statement\Preparable;


use OS\DatabaseAccessLayer\Driver;
use OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException;
use OS\DatabaseAccessLayer\Statement\ParameterContainerInterface;
use OS\DatabaseAccessLayer\Statement\PreparableStatement;
use OS\DatabaseAccessLayer\Statement\PreparedStatement;

abstract class AbstractPreparableStatement implements PreparableStatement
{
    /**
     * @param Driver $driver
     * @param ParameterContainerInterface $parameterContainer
     *
     * @return PreparedStatement
     * @throws StatementPreparationException
     */
    public function prepare(Driver $driver, ParameterContainerInterface $parameterContainer): PreparedStatement
    {
        $query = $this->toPreparableSql($driver->getSpecification(), $parameterContainer);
        $statement = $driver->prepare($query);

        $statement->setParameterContainer($parameterContainer);
        return $statement;
    }
}
