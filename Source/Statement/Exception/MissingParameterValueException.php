<?php

namespace OS\DatabaseAccessLayer\Statement\Exception;


class MissingParameterValueException extends StatementException
{
    public function __construct(string $missingParameterKey, \Throwable $previousException = null)
    {
        parent::__construct(
            sprintf('Missing a value of "%s".', $missingParameterKey),
            12345, //Todo: use reasonable error codes
            $previousException
        );
    }
}
