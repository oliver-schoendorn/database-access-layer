<?php

namespace OS\DatabaseAccessLayer\Driver\MysqlPdo\Exception;


use OS\DatabaseAccessLayer\Exception\DriverException;
use OS\DatabaseAccessLayer\Exception\UnknownDatabaseException;
use OS\DatabaseAccessLayer\Exception\UnreachableException;
use OS\DatabaseAccessLayer\Exception\AuthenticationException;

class PdoExceptionFactory
{
    private $exception;

    private $debug;

    public function __construct(\PDOException $exception, bool $debug = false)
    {
        $this->debug = $debug;
        $this->exception = $exception;
    }

    public function getException(): DriverException
    {
        switch ($this->exception->getCode()) {
            case '2002':
                return new UnreachableException(
                    $this->debug ? $this->exception->getMessage() : null,
                    $this->exception
                );
            case '1045':
                return new AuthenticationException(
                    $this->debug ? $this->exception->getMessage() : null,
                    $this->exception
                );
            case '1049':
                return new UnknownDatabaseException(
                    $this->debug ? $this->exception->getMessage() : null,
                    $this->exception
                );

            // @codeCoverageIgnoreStart
            default: return new DriverException(
                    'Unexpected exception: ' . $this->exception->getCode() .
                    ': ' . $this->exception->getMessage(),
                    0, $this->exception
            );
            // @codeCoverageIgnoreEnd
        }
    }
}
