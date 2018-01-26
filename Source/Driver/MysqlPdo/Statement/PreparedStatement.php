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

namespace OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement;


use OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;
use OS\DatabaseAccessLayer\Statement\ParameterContainer;
use OS\DatabaseAccessLayer\Statement\ParameterContainerInterface;
use OS\DatabaseAccessLayer\Statement\Result as StatementResult;

class PreparedStatement implements \OS\DatabaseAccessLayer\Statement\PreparedStatement
{
    /**
     * @var \PDOStatement
     */
    private $statement;

    /**
     * @var bool
     */
    private $debug;

    /**
     * PreparedStatement constructor.
     *
     * @param \PDOStatement $statement
     * @param bool $debug
     */
    public function __construct(\PDOStatement $statement, bool $debug = false)
    {
        $this->debug = $debug;
        $this->statement = $statement;
    }

    /**
     * @param ParameterContainerInterface|null $parameterContainer
     *
     * @return StatementResult
     * @throws MissingParameterValueException
     * @throws StatementExecutionException
     */
    public function execute(ParameterContainerInterface $parameterContainer = null): StatementResult
    {
        if ( ! is_null($parameterContainer)) {
            foreach ($parameterContainer->getParameters() as $parameter) {
                $parameter[2] = $this->parseParameterType($parameter[2]);
                $this->statement->bindParam(...$parameter);
            }
        }

        try {
            if ($this->statement->execute()) {
                return new Result($this->statement);
            }
            $errorInfo = $this->statement->errorInfo();
            throw new \RuntimeException($errorInfo[0].': '.$errorInfo[2], $errorInfo[1]);
        }
        catch (\PDOException $pdoException) {
            throw new StatementExecutionException(
                1013,
                $this->debug
                    ? $pdoException->getMessage() . ' - Query: ' . $this->getDebugData($parameterContainer)
                    : null,
                $pdoException
            );
        }
        catch (\Throwable $exception) {
            throw new StatementExecutionException(
                $this->debug ? $exception->getCode() : 1014,
                $this->debug
                    ? $exception->getMessage() . ' - Query: ' . $this->getDebugData($parameterContainer)
                    : null,
                $exception
            );
        }
    }

    private function parseParameterType(int $parameterType): int
    {
        switch ($parameterType) {
            case ParameterContainer::TYPE_NULL:
                return \PDO::PARAM_NULL;

            case ParameterContainer::TYPE_INT:
                return \PDO::PARAM_INT;

            case ParameterContainer::TYPE_BOOL:
                return \PDO::PARAM_BOOL;

            case ParameterContainer::TYPE_STRING:
                return \PDO::PARAM_STR;

            case ParameterContainer::TYPE_STREAM:
                return \PDO::PARAM_LOB;

            default: return \PDO::PARAM_STR; // @codeCoverageIgnore
        }
    }

    /**
     * @param ParameterContainerInterface|null $parameterContainer
     *
     * @return string
     * @throws MissingParameterValueException
     */
    public function getDebugData(ParameterContainerInterface $parameterContainer = null): string
    {
        if ( ! is_null($parameterContainer)) {
            return $this->interpolateQuery($parameterContainer);
        }

        return $this->statement->queryString ?? '';
    }

    /**
     * @param ParameterContainerInterface $parameterContainer
     *
     * @return string
     * @throws MissingParameterValueException
     */
    private function interpolateQuery(ParameterContainerInterface $parameterContainer): string
    {
        $query = $this->statement->queryString;

        $search = [];
        $replace = [];
        foreach ($parameterContainer->getParameters() as $parameter) {
            array_push($search, $parameter[0]);
            array_push($replace, $this->interpolateQueryValue($parameter[1], $parameter[2]));
        }
        return str_replace($search, $replace, $query);
    }

    /**
     * @param mixed $value
     * @param int $valueType
     *
     * @return string
     */
    private function interpolateQueryValue($value, int $valueType): string
    {
        switch ($valueType) {
            case ParameterContainerInterface::TYPE_NULL:
                return 'NULL';

            case ParameterContainerInterface::TYPE_INT:
                return (int) $value;

            case ParameterContainerInterface::TYPE_BOOL:
                return $value === true ? 'TRUE' : 'FALSE';

            case ParameterContainerInterface::TYPE_STRING:
                return sprintf('"%s"', $value);

            case ParameterContainerInterface::TYPE_STREAM:
                rewind($value);
                return sprintf('"%s"', stream_get_contents($value));
        }

        return '%UNDEFINED%'; // @codeCoverageIgnore
    }
}
