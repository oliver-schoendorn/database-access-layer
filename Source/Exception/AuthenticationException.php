<?php

namespace OS\DatabaseAccessLayer\Exception;


class AuthenticationException extends DriverException
{
    public function __construct($message, \Throwable $previousException = null)
    {
        parent::__construct(
            $message ?? 'Failed to authenticate with the database.',
            1001, $previousException
        );
    }
}
