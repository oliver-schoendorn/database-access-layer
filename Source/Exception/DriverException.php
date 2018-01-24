<?php

namespace OS\DatabaseAccessLayer\Exception;


use Throwable;

class DriverException extends \Exception
{
    public function __construct(
        string $message = null,
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message ?? 'Unexpected database driver exception', $code, $previous);
    }
}
