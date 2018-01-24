<?php

namespace OS\DatabaseAccessLayer\Statement\Exception;


class InvalidParameterTypeException extends StatementException
{
    public function __construct(int $invalidTypeId, \Throwable $previousException = null)
    {
        parent::__construct(
            sprintf('"%d" is not in the list of valid parameter types.', $invalidTypeId),
            1234, // Todo use reasonable codes
            $previousException
        );
    }
}
