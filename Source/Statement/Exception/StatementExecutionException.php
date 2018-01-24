<?php

namespace OS\DatabaseAccessLayer\Statement\Exception;


class StatementExecutionException extends StatementException
{
    /**
     * StatementExecutionException constructor.
     *
     * @param int $code
     * @param string|null $message
     * @param \Throwable|null $exception
     */
    public function __construct(int $code, $message, \Throwable $exception = null)
    {
        parent::__construct($message ?? 'Failed to execute database request.', $code, $exception);
    }
}
