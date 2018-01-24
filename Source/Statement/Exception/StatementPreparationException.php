<?php

namespace OS\DatabaseAccessLayer\Statement\Exception;


class StatementPreparationException extends StatementException
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
        parent::__construct($message ?? 'Failed to prepare database request.', $code, $exception);
    }
}
