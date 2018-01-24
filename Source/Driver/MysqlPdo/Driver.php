<?php
/**
 * Copyright (c) 2017 Oliver Schöndorn, Markus Schmidt
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

namespace OS\DatabaseAccessLayer\Driver\MysqlPdo;


use OS\DatabaseAccessLayer\Driver as DriverInterface;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Exception\PdoException;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Exception\PdoExceptionFactory;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Exception\PdoStatementExceptionFactory;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement\PreparedStatement;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Statement\Result;
use OS\DatabaseAccessLayer\Exception\AuthenticationException;
use OS\DatabaseAccessLayer\Exception\DriverException;
use OS\DatabaseAccessLayer\Specification as SpecificationInterface;
use OS\DatabaseAccessLayer\Statement\Exception\StatementException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException;
use OS\DatabaseAccessLayer\Statement\PreparedStatement as PreparedStatementInterface;
use OS\DatabaseAccessLayer\Statement\Result as StatementResult;
use OS\DatabaseAccessLayer\Config\DatabaseConfig;

class Driver implements DriverInterface
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var DatabaseConfig
     */
    private $config;

    /**
     * @var SpecificationInterface|Specification
     */
    private $specification;

    /**
     * Driver constructor.
     *
     * @param DatabaseConfig $config
     * @param SpecificationInterface $specification
     * @param array $options
     *
     * @throws DriverException
     *
     * @codeCoverageIgnore
     */
    public function __construct(DatabaseConfig $config, SpecificationInterface $specification = null, array $options = [])
    {
        $this->config = $config;
        $this->pdo = $this->connect($this->buildDsnString($config), $config->getUsername(), $config->getPassword(), $options);
        $this->specification = $specification ?? new Specification($this);
    }

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array  $options
     *
     * @return \PDO
     * @throws DriverException
     *
     * @codeCoverageIgnore
     */
    private function connect(string $dsn, string $username, string $password, array $options): \PDO
    {
        // Set default options
        $options = array_merge_recursive([
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false
        ], $options);

        // Try to connect
        try {
            return new \PDO($dsn, $username, $password, $options);
        }
        catch (\PDOException $exception) {
            throw (new PdoExceptionFactory($exception))->getException();
        }
    }

    private function buildDsnString(DatabaseConfig $config): string
    {
        $dsnParts = [];
        if ( ! empty($config->getHost())) {
            array_push($dsnParts, 'host=' . $config->getHost());
        }
        if ( ! empty($config->getPort()) && $config->getPort() > 0) {
            array_push($dsnParts, 'port=' . $config->getPort());
        }
        if ( ! empty($config->getName())) {
            array_push($dsnParts, 'dbname=' . $config->getName());
        }
        if ( ! empty($config->getCharset())) {
            array_push($dsnParts, 'charset=' . $config->getCharset());
        }

        return 'mysql:' .  implode(';', $dsnParts);
    }

    /**
     * @return SpecificationInterface|Specification
     */
    public function getSpecification(): SpecificationInterface
    {
        return $this->specification;
    }

    /**
     * @param string $value
     * @param int $valueType
     *
     * @return string
     */
    public function escape(string $value, int $valueType = \PDO::PARAM_STR): string
    {
        return $this->pdo->quote($value, $valueType);
    }

    /**
     * Returns the id of the last inserted row
     *
     * @return int
     */
    public function getLastInsertedId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return bool
     */
    public function transactionStart(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * @return bool
     */
    public function transactionAbort(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * @return bool
     */
    public function transactionCommit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * @param string $sql
     * @param array  $driverOptions
     *
     * @return StatementResult
     * @throws StatementExecutionException
     *
     * @codeCoverageIgnore
     */
    public function query(string $sql, array $driverOptions = []): StatementResult
    {
        try {
            $statement = $this->pdo->query($sql);
            if ( ! $statement) {
                $error = $this->pdo->errorInfo();
                throw new \RuntimeException($error[0].': '.$error[2], $error[1]);
            }
            $statement->execute();
        }
        catch(\Throwable $exception) {
            throw new StatementExecutionException(
                $this->config->isDebug() ? $exception->getCode() : 1011,
                $this->config->isDebug() ? $exception->getMessage() : null,
                $exception
            );
        }

        return new Result($statement);
    }

    /**
     * @param string $sql
     * @param array  $driverOptions
     *
     * @return PreparedStatementInterface
     * @throws StatementPreparationException
     *
     * @codeCoverageIgnore
     */
    public function prepare(string $sql, array $driverOptions = []): PreparedStatementInterface
    {
        try {
            $statement = $this->pdo->prepare($sql, $driverOptions);
            if ( ! $statement) {
                $error = $this->pdo->errorInfo();
                throw new \RuntimeException($error[0].': '.$error[2], $error[1]);
            }
        }
        catch(\Throwable $exception) {
            throw new StatementPreparationException(
                $this->config->isDebug() ? $exception->getCode() : 1012,
                $this->config->isDebug() ? $exception->getMessage() : null,
                $exception
            );
        }

        return new PreparedStatement($statement, $this->config->isDebug());
    }
}
