<?php

namespace OS\DatabaseAccessLayer\Exception;


class UnknownDatabaseException extends DriverException
{
    public function __construct($message, \Throwable $previousException = null)
    {
        parent::__construct(
            $message ?? 'The selected database does not exist.',
            1002, $previousException
        );
    }
}
