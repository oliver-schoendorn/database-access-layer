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

namespace OS\DatabaseAccessLayer\Statement\Preparable;


use OS\DatabaseAccessLayer\Driver;
use OS\DatabaseAccessLayer\Expression\Reference\FieldReference;
use OS\DatabaseAccessLayer\Expression\Reference\TableReference;
use OS\DatabaseAccessLayer\Specification;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidColumnKeyException;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidFetchTypeException;
use OS\DatabaseAccessLayer\Statement\Exception\InvalidParameterTypeException;
use OS\DatabaseAccessLayer\Statement\Exception\MissingColumnException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementPreparationException;
use OS\DatabaseAccessLayer\Statement\ParameterContainer;
use OS\DatabaseAccessLayer\Statement\ParameterContainerInterface;
use OS\DatabaseAccessLayer\Statement\PreparableStatement;

/**
 * Class Insert
 *
 * Usage example:
 *
 * ```php
 * <?php
 *
 * use OS\DatabaseAccessLayer\Expression\Reference\TableReference;
 * use OS\DatabaseAccessLayer\Statement\Preparable\Insert;
 *
 * $insert = new Insert(new TableReference('table_name'), [ 'id', 'name' ]);
 * $insert->addRow([ null, 'user name 1' ]);
 * $insert->addRow([ 'name' => 'user name 2' ]);
 * $insert->addRow([ 'name' => 'user name 3', 'id' => null ]);
 *
 * $response = $insert->execute($driver);
 * // $response = [ 2, 3, 4 ] <-- IDs of inserted rows
 *
 * ```
 *
 * @package OS\DatabaseAccessLayer\Statement\Preparable
 */
class Insert extends AbstractPreparableStatement implements PreparableStatement
{
    protected $format = 'INSERT INTO %s (%s) VALUES %s;';

    /**
     * @var TableReference
     */
    protected $table;

    /**
     * @var string[]
     */
    protected $columns = [];

    /**
     * @var array
     */
    protected $values = [];

    /**
     * Insert constructor.
     *
     * @param TableReference $table
     * @param FieldReference[]|string[] $columns
     */
    public function __construct(TableReference $table, array $columns = [])
    {
        $this->table = $table;
        $this->setColumns($columns);
    }

    /**
     * @param FieldReference[]|string[] $columns
     *
     * @return $this
     */
    public function setColumns(array $columns = [])
    {
        $this->columns = array_map(
            function($column) {
                return $column instanceof FieldReference
                    ? $column->getName()
                    : $column;
            },
            $columns
        );

        return $this;
    }

    /**
     * Returns all column ids as plain strings
     *
     * @return string[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Adds a new row-set
     *
     * This method expects the value for an entire row. It is recommended to use an associative array with
     * the column id as key and the column value as value.
     *
     * @param array $values
     *
     * @return $this
     */
    public function addRow(array $values)
    {
        array_push($this->values, $values);
        return $this;
    }

    /**
     * Returns the rows, that should be inserted (or have been, if the execute method was invoked)
     *
     * @return array
     */
    public function getRows(): array
    {
        return $this->values;
    }

    /**
     * @param Specification $specification
     *
     * @return string
     * @throws InvalidColumnKeyException
     * @throws MissingColumnException
     */
    public function toSql(Specification $specification): string
    {
        $keys = $this->getColumnIdentifiers($specification);
        $values = [];

        foreach (array_keys($this->values) as $index) {
            $row = $this->getRowValues($index);
            $row = $this->getRowValuesOrdered($row);
            $row = $this->quoteRowValues($row, $specification);
            array_push($values, '(' . implode(', ', $row) . ')');
        }

        return sprintf($this->format, $this->table->toSql($specification), implode(', ', $keys), implode(', ', $values));
    }

    /**
     * @param Specification $specification
     * @param ParameterContainerInterface $container
     *
     * @return string
     * @throws MissingColumnException
     */
    public function toPreparableSql(Specification $specification, ParameterContainerInterface &$container): string
    {
        $keys = $this->getColumnIdentifiers($specification);
        $values = $this->getColumnParameterKeys($container);
        return sprintf($this->format, $this->table->toSql($specification), implode(', ', $keys), '(' . implode(', ', $values) . ')');
    }

    /**
     * @param Specification $specification
     *
     * @return array
     * @throws MissingColumnException
     */
    private function getColumnIdentifiers(Specification $specification): array
    {
        if (count($this->columns) === 0) {
            throw new MissingColumnException('Insert failed: Missing column information.');
        }

        return array_map([ $specification, 'quoteIdentifier' ], $this->columns);
    }

    /**
     * @param ParameterContainerInterface $container
     *
     * @return array
     */
    private function getColumnParameterKeys(ParameterContainerInterface $container): array
    {
        return array_map(function($key) use($container) {
            return ':' . $container->addParameter($key, $container::TYPE_STRING);
        }, $this->columns);
    }

    /**
     * @param int|string $index
     *
     * @return array
     * @throws InvalidColumnKeyException
     */
    private function getRowValues($index): array
    {
        $row = $this->values[$index];
        $values = [];
        foreach ($row as $key => $value) {
            $columnKey = $this->getColumnByKey($key);
            $values[$columnKey] = $value ?? null;
        }

        return $values;
    }

    /**
     * @param string|int $key
     *
     * @return string
     * @throws InvalidColumnKeyException
     */
    private function getColumnByKey($key): string
    {
        if (is_numeric($key)) {
            if ( ! array_key_exists($key, $this->columns)) {
                throw new InvalidColumnKeyException('Insert failed: One of the provided rows has a value key that is out of bounds of the defined columns.');
            }

            return $this->columns[$key];
        }

        if ( ! in_array($key, $this->columns)) {
            throw new InvalidColumnKeyException('Insert failed: One of the provided rows uses a key that does not map to the defined columns.');
        }

        return $key;
    }

    /**
     * @param array $values
     *
     * @return array
     */
    private function getRowValuesOrdered(array $values): array
    {
        $ordered = [];
        foreach ($this->columns as $column) {
            $ordered[$column] = $values[$column] ?? null;
        }
        return $ordered;
    }

    /**
     * @param array $values
     * @param Specification $specification
     *
     * @return array
     */
    private function quoteRowValues(array $values, Specification $specification): array
    {
        foreach ($values as &$value) {
            $value = $specification->quoteValue($value);
        }

        return $values;
    }

    /**
     * @param Driver $driver
     *
     * @return int[] inserted ids
     *
     * @throws StatementPreparationException
     * @throws MissingColumnException
     * @throws \Throwable
     */
    public function execute(Driver $driver): array
    {
        $container = new ParameterContainer();
        $query = $this->toPreparableSql($driver->getSpecification(), $container);
        $statement = $driver->prepare($query);
        $statement->setParameterContainer($container);

        $insertedIds = [];

        $driver->transactionStart();
        try {
            foreach (array_keys($this->values) as $key) {
                $row = $this->getRowValues($key);
                $this->bindValues($row, $container);
                $statement->execute();
                array_push($insertedIds, $driver->getLastInsertedId());
            }
        }
        catch (\Throwable $exception) {
            $driver->transactionAbort();
            throw $exception;
        }
        $driver->transactionCommit();

        return $insertedIds;
    }

    /**
     * @param array $rowValues
     * @param ParameterContainerInterface $container
     *
     * @return void
     * @throws InvalidParameterTypeException
     */
    private function bindValues(array $rowValues, ParameterContainerInterface $container)
    {
        foreach ($rowValues as $key => $value) {
            $container->setValue($key, $value, $container::guessParameterType($value));
        }
    }
}
