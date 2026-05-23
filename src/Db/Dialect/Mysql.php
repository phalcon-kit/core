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

namespace PhalconKit\Db\Dialect;

use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Dialect;

/**
 * MySQL dialect with PhalconKit query helpers.
 *
 * The dialect registers custom PHQL functions that are commonly used by the
 * framework query builders:
 *
 * - `regexp(left, right)` renders `left REGEXP right`
 * - `ST_Distance_Sphere(left, right)` renders MySQL spherical distance SQL
 * - `point(left, right)` renders a MySQL point expression
 *
 * It also keeps a compatibility fallback for binary column definitions affected
 * by upstream Phalcon behavior.
 */
class Mysql extends \Phalcon\Db\Dialect\Mysql
{
    /**
     * Register PhalconKit custom SQL functions on construction.
     */
    public function __construct()
    {
        $this->registerRegexpFunction();
        $this->registerDistanceSphereFunction();
        $this->registerPointFunction();
    }
    
    /**
     * Register the PHQL `regexp()` helper for MySQL `REGEXP` comparisons.
     *
     * @return void
     */
    public function registerRegexpFunction(): void
    {
        $this->registerCustomFunction('regexp', function (Dialect $dialect, array $expression) {
            $arguments = $expression['arguments'] ?? [];
            return sprintf(
                " %s REGEXP %s",
                $dialect->getSqlExpression($arguments[0]),
                $dialect->getSqlExpression($arguments[1])
            );
        });
    }
    
    /**
     * Register the PHQL `ST_Distance_Sphere()` helper for geospatial queries.
     *
     * The SQL function expects two point expressions and returns the spherical
     * distance in meters on supported MySQL/MariaDB versions.
     *
     * @return void
     */
    public function registerDistanceSphereFunction(): void
    {
        $this->registerCustomFunction('ST_Distance_Sphere', function (Dialect $dialect, array $expression) {
            $arguments = $expression['arguments'] ?? [];
            return sprintf(
                " ST_Distance_Sphere(%s, %s)",
                $dialect->getSqlExpression($arguments[0]),
                $dialect->getSqlExpression($arguments[1]),
            );
        });
    }
    
    /**
     * Register the PHQL `point()` helper for MySQL point expressions.
     *
     * @return void
     */
    public function registerPointFunction(): void
    {
        $this->registerCustomFunction('point', function (Dialect $dialect, array $expression) {
            $arguments = $expression['arguments'] ?? [];
            return sprintf(
                " point(%s, %s)",
                $dialect->getSqlExpression($arguments[0]),
                $dialect->getSqlExpression($arguments[1]),
            );
        });
    }
    
    /**
     * Return a SQL column definition with a binary-type compatibility fallback.
     *
     * Phalcon can throw while rendering binary and varbinary columns in versions
     * affected by upstream issue https://github.com/phalcon/cphalcon/issues/16532.
     * For every other column type the native implementation remains authoritative.
     *
     * @param ColumnInterface $column Column metadata to render.
     *
     * @return string SQL fragment for the column type and size.
     */
    #[\Override]
    public function getColumnDefinition(ColumnInterface $column): string
    {
        try {
            return parent::getColumnDefinition($column);
        }
        catch (\Phalcon\Db\Exception $e) {
            $columnSql = $this->checkColumnTypeSql($column);
            $columnType = $this->checkColumnType($column);
            
            switch ($columnType) {
                case Column::TYPE_BINARY:
                    if (empty($columnSql)) {
                        $columnSql .= 'BINARY';
                    }
                    if ($column->getSize() > 0) {
                        $columnSql .= $this->getColumnSize($column);
                    }
                    break;
                
                case Column::TYPE_VARBINARY:
                    if (empty($columnSql)) {
                        $columnSql .= 'VARBINARY';
                    }
                    if ($column->getSize() > 0) {
                        $columnSql .= $this->getColumnSize($column);
                    }
                    break;
            }
            
            return $columnSql;
        }
    }
}
