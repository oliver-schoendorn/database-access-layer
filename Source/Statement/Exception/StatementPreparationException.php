<?php
/**
 * Copyright (c) 2018 Oliver Schöndorn
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
