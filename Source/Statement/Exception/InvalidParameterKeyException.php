<?php

namespace OS\DatabaseAccessLayer\Statement\Exception;


class InvalidParameterKeyException extends StatementException
{
    public function __construct(string $invalidKey, \Throwable $previousException = null)
    {
        parent::__construct(
            sprintf('The key "%s" can not be used as parameter name.', $invalidKey),
            123456,
            $previousException
        );
    }
}
