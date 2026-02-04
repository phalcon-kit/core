<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Modules\Cli\Tasks\Traits;

use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use PhalconKit\Support\Helper;

/**
 * Trait DescribesTrait
 *
 * This trait provides methods for describing table columns, references,
 * indexes, and determining the data type of columns. It also provides
 * methods for retrieving default values of columns, generating property
 * names based on column names, and generating table names based on
 * original names.
 */
trait DescribesTrait
{
    protected array $cachedColumns = [];
    protected array $cachedIndexes = [];
    protected array $cachedReferences = [];

    /**
     * Retrieves the columns of a given table.
     * @param string $table The name of the table to describe the columns.
     * @return array An array of columns for the specified table.
     */
    public function describeColumns(string $table): array
    {
        return $this->cachedColumns[$table] ??= $this->db->describeColumns($table);
    }

    /**
     * Retrieves the references of a given table.
     * @param string $table The name of the table to describe the references.
     * @return array An array of references for the specified table.
     */
    public function describeReferences(string $table): array
    {
        return $this->cachedReferences[$table] ??= $this->db->describeReferences($table);
    }

    /**
     * Retrieves the indexes of a given table.
     * @param string $table The name of the table to describe the indexes.
     * @return array An array of indexes for the specified table.
     */
    public function describeIndexes(string $table): array
    {
        return $this->cachedIndexes[$table] ??= $this->db->describeIndexes($table);
    }

    /**
     * Determines if a value is a Phalcon DB RawValue.
     * @param string|null $defaultValue The value to check.
     * @return bool Returns true if the value is a raw value, false otherwise.
     */
    public function isRawValue(?string $defaultValue = null): bool
    {
        return match ($defaultValue) {
            'CURRENT_TIMESTAMP',
            'NOW()',
            'CURDATE()',
            'CURTIME()',
            'UNIX_TIMESTAMP()',
            'RAND()',
            'UUID()',
            'USER()',
            'CONNECTION_ID()'
            => true,

            default
            => false,
        };
    }

    /**
     * Determines the PHP data type of column.
     *
     * @param ColumnInterface $column The column to check.
     *
     * @return string The data type of the column. Possible values are:
     *                - 'bool' for boolean columns.
     *                - 'int' for integer columns.
     *                - 'float' for decimal or float columns.
     *                - 'double' for double columns.
     *                - 'string' for all other column types.
     */
    public function getColumnType(ColumnInterface $column): string
    {
        return match ($column->getType()) {
            Column::TYPE_BOOLEAN
            => 'bool',

            Column::TYPE_TIMESTAMP,
            Column::TYPE_BIGINTEGER,
            Column::TYPE_MEDIUMINTEGER,
            Column::TYPE_SMALLINTEGER,
            Column::TYPE_TINYINTEGER,
            Column::TYPE_INTEGER,
            Column::TYPE_BIT
            => 'int',

            Column::TYPE_DECIMAL,
            Column::TYPE_FLOAT
            => 'float',

            Column::TYPE_DOUBLE
            => 'double',

            default
            => 'string',
        };
    }

    /**
     * Retrieves the default value for a column.
     * @param ColumnInterface $column The column object to retrieve the default value from.
     * @return mixed Returns the default value of the column as a string, integer, boolean, float, or null based on the column type.
     */
    public function getDefaultValue(ColumnInterface $column): mixed
    {
        if (!$column->hasDefault()) {
            return null;
        }

        $columnDefault = $column->getDefault();
        if (!isset($columnDefault)) {
            return null;
        }

        $type = $this->getColumnType($column);
        return match ($type) {
            'bool' => (bool)$columnDefault,
            'int' => (int)$columnDefault,
            'double' => (double)$columnDefault,
            'float' => (float)$columnDefault,
            default => $this->isRawValue($columnDefault) ? null
                : '\'' . addslashes((string)$columnDefault) . '\'',
        };
    }

    /**
     * Retrieves the property name based on the given name.
     * @param string $name The name from which to retrieve the property name.
     * @return string Returns the property name as a string.
     */
    public function getPropertyName(string $name): string
    {
        return lcfirst(
            Helper::camelize(
                Helper::uncamelize(
                    $name
                )
            )
        );
    }

    /**
     * Retrieves the table name based on the given name.
     * @param string $name The original name of the table.
     * @return string Returns the table name with the first letter capitalized and all other letters unchanged.
     */
    public function getTableName(string $name): string
    {
        return ucfirst(
            Helper::camelize(
                Helper::uncamelize(
                    $name
                )
            )
        );
    }

    /**
     * Wraps a property name in square brackets if certain conditions are met.
     * Note: fields that are already wrapped will not be wrapped again.
     *
     * @param string $name The name of the property to be wrapped.
     * @param bool $always Indicates whether the property name should always be wrapped, regardless of other conditions.
     *
     * @return string The property name, optionally wrapped in square brackets.
     */
    public function wrapIdentifier(string $name, bool $always = false): string
    {
        if ($this->requiresWrapping($name, $always)) {
            return "[{$name}]";
        }

        return $name;
    }

    /**
     * Determines whether a property name should be wrapped based on specific conditions.
     * Reasoning: Phalcon PHQL parser has a bug with function names starting with 'not'.
     *
     * @param string $name The property name to check for wrapping.
     * @param bool $always Whether to always wrap the property name regardless of other conditions.
     * @return bool True if the property name should be wrapped, otherwise false.
     */
    public function requiresWrapping(string $name, bool $always = false): bool
    {
        // already wrapped
        if (str_starts_with($name, '[')) {
            return false;
        }

        if ($always) {
            return true;
        }

        $lowerName = strtolower($name);
        if (str_starts_with($lowerName, 'not')) {
            return true;
        }

        return false;
    }
}
