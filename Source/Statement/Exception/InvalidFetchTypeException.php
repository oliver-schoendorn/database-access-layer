<?php

namespace OS\DatabaseAccessLayer\Statement\Exception;


class InvalidFetchTypeException extends StatementException
{
    public function __construct(int $givenFetchType, \Throwable $previous = null)
    {
        parent::__construct(
            sprintf('The given fetch type "%d" is invalid.', $givenFetchType),
            1234567, // Todo: Use reasonable exception codes
            $previous
        );
    }
}
