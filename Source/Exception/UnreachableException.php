<?php

namespace OS\DatabaseAccessLayer\Exception;


class UnreachableException extends DriverException
{
    public function __construct($message, \Throwable $previousException = null)
    {
        parent::__construct(
            $message ?? 'The database host or network is unreachable.',
            1003, $previousException
        );
    }
}
