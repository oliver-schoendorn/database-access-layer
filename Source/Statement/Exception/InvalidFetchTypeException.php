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
