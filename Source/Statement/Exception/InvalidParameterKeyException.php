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
