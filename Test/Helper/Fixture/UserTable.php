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

namespace OS\DatabaseAccessLayer\Test\Helper\Fixture;


use PHPUnit\DbUnit\DataSet\DefaultTableMetadata;
use PHPUnit\DbUnit\DataSet\ITableMetadata;

class UserTable extends TableFixture
{
    public function getTableName(): string
    {
        return 'acceptance_test_user';
    }

    protected function createTable(\PDO $pdo)
    {
        $pdo->exec('
            CREATE TABLE `' . $this->getTableName() . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) COLLATE utf8_bin NOT NULL,
                `password` binary(255) NOT NULL,
                `created` datetime NOT NULL,
                `nullable` int(11) DEFAULT NULL,
                `bool` tinyint(1) DEFAULT \'0\',
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_id_uindex` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ');
    }

    protected function getTableMeta(): ITableMetadata
    {
        return new DefaultTableMetadata($this->getTableName(), [
            'id', 'name', 'password', 'created', 'nullable', 'bool'
        ], ['id']);
    }
}
