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


abstract class AbstractConfig
{
    public function __construct(array $configuration)
    {
        $this->initializeFromArray($configuration);
    }

    private function initializeFromArray(array $configuration)
    {
        foreach ($configuration as $key => $value)
        {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this as $key => $value)
        {
            $array[$key] = $value;
        }
        return $array;
    }
}
