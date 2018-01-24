<?php
/**
 * Copyright (c) 2017 Oliver Schöndorn, Markus Schmidt
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

namespace OS\DatabaseAccessLayer\Config;


class DatabaseConfig extends AbstractConfig
{
    /**
     * Database host
     * @var string
     */
    protected $host = '';

    /**
     * Database port
     * @var int
     */
    protected $port = -1;

    /**
     * Database name
     * @var string
     */
    protected $name = '';

    /**
     * Database user name
     * @var string
     */
    protected $username = '';

    /**
     * Database user password
     * @var string
     */
    protected $password = '';

    /**
     * Database connection charset
     * @var string
     */
    protected $charset = 'utf8mb4';

    /**
     * If set to true, exception message will contain additional information
     *
     * Do not use in production, as these message can leak connection details, credentials and
     * other private data!
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }
}
