<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Modules\Cli\Tasks;

use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\IndexInterface;
use PhalconKit\Modules\Cli\Task;
use PhalconKit\Modules\Cli\Tasks\Traits\DescribesTrait;
use PhalconKit\Modules\Cli\Tasks\Traits\ScaffoldTrait;
use PhalconKit\Support\Helper;
use PhalconKit\Support\Slug;

class ScaffoldTask extends Task
{
    use ScaffoldTrait;
    use DescribesTrait;
    
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli scaffold <action> [--force] [--directory=<directory>] [--namespace=<namespace>]
                              [--table=<table>] [--exclude=<exclude>] [--license=<license>]
                              [--src-dir=<src-dir>] [--controllers-dir=<controllers-dir>]
                              [--interfaces-dir=<interfaces-dir>] [--abstracts-dir=<abstracts-dir>]
                              [--models-dir=<models-dir>] [--enums-dir=<enums-dir>] [--tests-dir=<tests-dir>]
                              [--models-extend=<models-extend>] [--interfaces-extend=<interface-extend>]
                              [--controllers-extend=<controllers-extend>] [--tests-extend=<tests-extend>]
                              [--no-controllers] [--no-interfaces] [--no-abstracts] [--no-models] [--no-enums] [--no-tests]
                              [--no-strict-types] [--no-license] [--no-comments] [--no-get-set-methods]
                              [--no-validations] [--no-relationships] [--no-column-map] [--no-set-source]
                              [--no-typings] [--granular-typings] [--add-raw-value-type] [--protected-properties]

Actions:
  models
  abstracts
  controllers
  enums

Options:
  --force                                     Overwrite existing files
  --table=<table>                             Comma seperated list of table to generate
  --exclude=<table>                           Comma seperated list of table to exclude
  --namespace=<namespace>                     Root namespace of the project (Default to "App")
  --license=<license>                         Set your own license stamp (PHP Comment)

  --directory=<directory>                     Root directory path to generate new files (Default to "./")
  --src-dir=<src-dir>                         Source directory path to generate new files (Default to "src/")
  --controllers-dir=<controllers-dir>         Set your own controllers directory (Default: "Controllers")
  --interfaces-dir=<interfaces-dir>           Set your own interfaces directory (Default: "Interfaces")
  --abstracts-dir=<abstracts-dir>             Set your own abstract directory (Default: "Abstracts")
  --models-dir=<models-dir>                   Set your own models directory (Default: "Models")
  --enums-dir=<enums-dir>                     Set your own enums directory (Default: "Enums")
  --tests-dir=<tests-dir>                     Set your own tests directory (Default: "Tests")

  --models-extend=<models-extend>             Extend models with this base class (Default: "\PhalconKit\Models\ModelAbstract")
  --interfaces-extend=<interface-extend>      Extend models interfaces with this base interface (Default: "\PhalconKit\Models\ModelInterface")
  --controllers-extend=<controllers-extends>  Extend controllers with this base class (Default: "\PhalconKit\Mvc\Controller\Rest")
  --tests-extend=<tests-extends>              Extend tests with this base class (Default: "\PhalconKit\Tests\Unit\AbstractUnit")

  --no-controllers                            Do not generate controllers
  --no-interfaces                             Do not generate interfaces
  --no-abstracts                              Do not generate abstracts
  --no-models                                 Do not generate models
  --no-enums                                  Do not generate enums
  --no-tests                                  Do not generate tests

  --no-strict-types                           Do not generate declare(strict_types=1);
  --no-license                                Do not generate license stamps
  --no-comments                               Do not generate comments

  --no-get-set-methods                        Do not generate getter and setter methods in models
  --no-validations                            Do not generate default validations in models
  --no-relationships                          Do not generate default relationships in models
  --no-column-map                             Do not generate column map in models
  --no-set-source                             Do not call setSource() in models
  --no-typings                                Do not generate typings for properties in models
  --granular-typings                          Force the properties to `mixed` in models
  --add-raw-value-type                        Add the `RawValue` type to every property in models
  --protected-properties                      Make the properties `protected` in models
DOC;
    
    public function getDefinitionsAction(string $name): array
    {
        $definitions = [];
        
        $tableName = $this->getTableName($name);
        $definitions['table'] = $tableName;
        $definitions['slug'] = Slug::generate(Helper::uncamelize($name));
        
        // enums
        $definitions['enums']['name'] = $tableName;
        $definitions['enums']['file'] = $definitions['enums']['name'] . '.php';
        
        // controllers
        $definitions['controller']['name'] = $tableName . 'Controller';
        $definitions['controller']['file'] = $definitions['controller']['name'] . '.php';
        
        // controllers interfaces
        $definitions['controllerInterface']['name'] = $definitions['controller']['name'] . 'Interface';
        $definitions['controllerInterface']['file'] = $definitions['controllerInterface']['name'] . '.php';
        
        // abstracts
        $definitions['abstract']['name'] = $tableName . 'Abstract';
        $definitions['abstract']['file'] = $definitions['abstract']['name'] . '.php';
        
        // abstracts interfaces
        $definitions['abstractInterface']['name'] = $definitions['abstract']['name'] . 'Interface';
        $definitions['abstractInterface']['file'] = $definitions['abstractInterface']['name'] . '.php';
        
        // models
        $definitions['model']['name'] = $tableName;
        $definitions['model']['file'] = $definitions['model']['name'] . '.php';
        
        // models interfaces
        $definitions['modelInterface']['name'] = $definitions['model']['name'] . 'Interface';
        $definitions['modelInterface']['file'] = $definitions['modelInterface']['name'] . '.php';
        
        // models tests
        $definitions['modelTest']['name'] = $definitions['model']['name'] . 'Test';
        $definitions['modelTest']['file'] = $definitions['modelTest']['name'] . '.php';
        
        return $definitions;
    }
    
    public function runAction(): array
    {
        $ret = [];
        
        $force = $this->dispatcher->getParam('force') ?? false;
        
        $tables = $this->db->listTables();
        foreach ($tables as $table) {
            // filter excluded tables
            if ($this->isExcludedTable($table)) {
                continue;
            }
            
            // filter whitelisted tables
            if (!$this->isWhitelistedTable($table)) {
                continue;
            }
            
            $columns = $this->describeColumns($table);
            $indexes = $this->describeIndexes($table);
            $references = $this->describeReferences($table);
            $definitions = $this->getDefinitionsAction($table);
            $relationships = $this->getRelationshipItems($table, $columns, $tables);
            
            // Controller
//            $savePath = $this->getControllersDirectory($definitions['controller']['file']);
//            if (!file_exists($savePath) || $force) {
//                $this->saveFile($savePath, $this->createControllerOutput($definitions, $columns, $relationships), $force);
//                $ret [] = 'Controller API `' . $definitions['controller']['file'] . '` created';
//            }
            
            // Abstract
            if (!$this->isNoAbstracts()) {
                $savePath = $this->getAbstractsDirectory($definitions['abstract']['file']);
                if (!file_exists($savePath) || $force) {
                    $this->saveFile($savePath, $this->createAbstractOutput($definitions, $columns, $relationships, $indexes), $force);
                    $ret [] = 'Abstract Model `' . $definitions['abstract']['file'] . '` created at `' . $savePath . '`';
                }
            }

            
            // Abstract Interfaces
            if (!$this->isNoInterfaces()) {
                $savePath = $this->getAbstractsInterfacesDirectory($definitions['abstractInterface']['file']);
                if (!file_exists($savePath) || $force) {
                    $this->saveFile($savePath, $this->createAbstractInterfaceOutput($definitions, $columns, $relationships), $force);
                    $ret [] = 'Abstract Model Interface `' . $definitions['abstractInterface']['file'] . '` created at `' . $savePath . '`';
                }
            }

            // Model Enums
            foreach ($columns as $column) {
                assert($column instanceof Column);
                if ($column->getType() === Column::TYPE_ENUM) {
                    $enumValues = $column->getSize();
                    
                    $enumName = $definitions['enums']['name'] . Helper::camelize($column->getName());
                    $definitionsEnumFile = $enumName . '.php';
                    $savePath = $this->getEnumsDirectory($definitionsEnumFile);
                    if (!file_exists($savePath) || $force) {
                        $this->saveFile($savePath, $this->createEnumOutput($enumName, $column), $force);
                        $ret [] = 'Enum `' . $definitionsEnumFile . '` created at `' . $savePath . '`';
                    }
                }
            }
            
            // Model
            if (!$this->isNoModels()) {
                $savePath = $this->getModelsDirectory($definitions['model']['file']);
                if (!file_exists($savePath) || $force) {
                    $this->saveFile($savePath, $this->createModelOutput($definitions), $force);
                    $ret [] = 'Model `' . $definitions['model']['file'] . '` created at `' . $savePath . '`';
                }
            }

            // Model Interfaces
            if (!$this->isNoInterfaces()) {
                $savePath = $this->getModelsInterfacesDirectory($definitions['modelInterface']['file']);
                if (!file_exists($savePath) || $force) {
                    $this->saveFile($savePath, $this->createModelInterfaceOutput($definitions), $force);
                    $ret [] = 'Model Interface `' . $definitions['modelInterface']['file'] . '` created at `' . $savePath . '`';
                }
            }

            
            // Model Test
            $savePath = $this->getModelsTestsDirectory($definitions['modelTest']['file']);
            if (!file_exists($savePath) || $force) {
                $this->saveFile($savePath, $this->createModelTestOutput($definitions, $columns), $force);
                $ret [] = 'Model Test `' . $definitions['modelTest']['file'] . '` created at `' . $savePath . '`';
            }
        }
        
        return $ret;
    }
    
    public function createControllerOutput(array $definitions, array $columns, array $relationships): string
    {
        return <<<PHP
<?php
{$this->getLicenseStamp()}
{$this->getStrictTypes()}
namespace {$this->getAbstractsInterfacesNamespace()};

use Phalcon\Db\RawValue;
use PhalconKit\Modules\Api\Controller\ControllerAbstract;

/**
{$relationships['interfaceInjectableItems']}
 */
interface {$definitions['controller']['name']} extends ControllerAbstract
{
}

PHP;
    }
    
    /**
     * Generates the output for a model interface.
     *
     * @param array $definitions The definitions for generating the model interface.
     *
     * @return string The generated model interface output as a string.
     */
    public function createModelInterfaceOutput(array $definitions): string
    {
        return <<<PHP
<?php
{$this->getLicenseStamp()}
{$this->getStrictTypes()}
namespace {$this->getModelsInterfacesNamespace()};

use {$this->getAbstractsInterfacesNamespace()}\\{$definitions['abstractInterface']['name']};

interface {$definitions['modelInterface']['name']} extends {$definitions['abstractInterface']['name']}
{
}

PHP;
    }
    
    /**
     * Generates the abstract interface output based on the given definitions and columns.
     *
     * @param array $definitions The definitions for the abstract interface.
     * @param array $columns The columns for which to generate getter and setter methods.
     * @param array $relationships The columns for which to generate getter and setter methods.
     *
     * @return string The generated abstract interface output as a string.
     */
    public function createAbstractInterfaceOutput(array $definitions, array $columns, array $relationships): string
    {
        $getSetInterfaceItems = $this->getGetSetMethods($columns, 'interface');
        return <<<PHP
<?php
{$this->getLicenseStamp()}
{$this->getStrictTypes()}
namespace {$this->getAbstractsInterfacesNamespace()};

use Phalcon\Db\RawValue;
use PhalconKit\Mvc\ModelInterface;

/**
{$relationships['interfaceInjectableItems']}
 */
interface {$definitions['abstractInterface']['name']} extends ModelInterface
{
    {$getSetInterfaceItems}
}

PHP;
    }
    
    /**
     * Generates an abstract class output for the given definitions, table, columns, and tables.
     *
     * @param array $definitions The definitions for the abstract output.
     * @param array $columns The columns.
     * @param array $relationships The relationship items.
     *
     * @return string The abstract output as a string.
     */
    public function createAbstractOutput(array $definitions, array $columns, array $relationships, array $indexes): string
    {
        $propertyItems = $this->getPropertyItems($columns);
        $getSetMethods = $this->getGetSetMethods($columns);
        $columnMapMethod = $this->getColumnMapMethod($columns);
        $validationItems = $this->getValidationItems($columns, $indexes);
        $modelsExtend = $this->getModelsExtend();
        $modelsExtendBaseName = basename($modelsExtend);
        
        return <<<PHP
<?php
{$this->getLicenseStamp()}
{$this->getStrictTypes()}
namespace {$this->getAbstractsNamespace()};

use Phalcon\Db\RawValue;
use PhalconKit\Filter\Validation;
use {$modelsExtend};
{$relationships['useItems']}
use {$this->getAbstractsInterfacesNamespace()}\\{$definitions['abstractInterface']['name']};

/**
 * Class {$definitions['abstract']['name']}
 *
 * This class defines a {$definitions['model']['name']} abstract model that extends the AbstractModel class and implements the {$definitions['abstractInterface']['name']}.
 * It provides properties and methods for managing {$definitions['model']['name']} data.
 *
{$relationships['injectableItems']}
 */
abstract class {$definitions['abstract']['name']} extends {$modelsExtendBaseName} implements {$definitions['abstractInterface']['name']}
{
    {$propertyItems}
    
    {$getSetMethods}

    /**
     * Adds the default relationships to the model.
     * @return void
     */
    public function addDefaultRelationships(): void
    {
        {$relationships['items']}
    }
    
    /**
     * Adds the default validations to the model.
     * @param Validation|null \$validator
     * @return Validation
     */
    public function addDefaultValidations(?Validation \$validator = null): Validation
    {
        \$validator ??= new Validation();
    
        {$validationItems}
        
        return \$validator;
    }

    {$columnMapMethod}
}

PHP;
    }
    
    public function createEnumOutput(string $enumName, Column $column): string
    {
        $size = $column->getSize();
        $list = explode(',', str_replace('\'', '', (string)$size));
        $enumValues = [];
        foreach ($list as $item) {
            $constantName = Helper::upper(Helper::slugify(Helper::uncamelize($item), [], '_')) ?: '_EMPTY';
            $enumValues[] = '    case ' . $constantName . ' = \'' . $item . '\';';
        }
        $enumValues = implode(PHP_EOL, $enumValues);
        return <<<PHP
<?php
{$this->getLicenseStamp()}
{$this->getStrictTypes()}
namespace {$this->getEnumsNamespace()};

enum {$enumName}: string {
{$enumValues}
}
PHP;
    }
    
    /**
     * Generates a comment for the createModelOutput method.
     *
     * @param array $definitions The array of model definitions.
     * @return string The generated comment.
     */
    public function createModelOutput(array $definitions): string
    {
        return <<<PHP
<?php
{$this->getLicenseStamp()}
{$this->getStrictTypes()}
namespace {$this->getModelsNamespace()};

use {$this->getAbstractsNamespace()}\\{$definitions['abstract']['name']};
use {$this->getModelsInterfacesNamespace()}\\{$definitions['modelInterface']['name']};
{$this->getModelClassComments($definitions)}
class {$definitions['model']['name']} extends {$definitions['abstract']['name']} implements {$definitions['modelInterface']['name']}
{
    public function initialize(): void
    {
        parent::initialize();
        \$this->addDefaultRelationships();
    }

    public function validation(): bool
    {
        \$validator = \$this->genericValidation();
        \$this->addDefaultValidations(\$validator);
        return \$this->validate(\$validator);
    }
}

PHP;
    }
    
    public function createModelTestOutput(array $definitions, array $columns): string
    {
        $property = lcfirst($definitions['model']['name']);
        $getSetTestItems = $this->getGetSetMethods($columns, 'test', $property);
        return <<<PHP
<?php
{$this->getLicenseStamp()}
{$this->getStrictTypes()}
namespace {$this->getModelsTestsNamespace()};

use {$this->getAbstractsNamespace()}\\{$definitions['abstract']['name']};
use {$this->getAbstractsInterfacesNamespace()}\\{$definitions['abstractInterface']['name']};
use {$this->getModelsNamespace()}\\{$definitions['model']['name']};
use {$this->getModelsInterfacesNamespace()}\\{$definitions['modelInterface']['name']};

/**
 * Class {$definitions['modelTest']['name']}
 *
 * This class contains unit tests for the User class.
 */
class {$definitions['modelTest']['name']} extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public {$definitions['modelInterface']['name']} \${$property};
    
    protected function setUp(): void
    {
        \$this->{$property} = new {$definitions['model']['name']}();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        \$this->assertInstanceOf({$definitions['model']['name']}::class, \$this->{$property});
        \$this->assertInstanceOf({$definitions['modelInterface']['name']}::class, \$this->{$property});
    
        // Abstract
        \$this->assertInstanceOf({$definitions['abstract']['name']}::class, \$this->{$property});
        \$this->assertInstanceOf({$definitions['abstractInterface']['name']}::class, \$this->{$property});
        
        // Phalcon Kit
        \$this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, \$this->{$property});
        \$this->assertInstanceOf(\PhalconKit\Mvc\Model::class, \$this->{$property});
        
        // Phalcon
        \$this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, \$this->{$property});
        \$this->assertInstanceOf(\Phalcon\Mvc\Model::class, \$this->{$property});
    }
    
    {$getSetTestItems}
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        \$this->assertIsArray(\$this->{$property}->getColumnMap());
    }
}

PHP;
    }
    
    public function getModelClassComments(array $definitions): string
    {
        if ($this->isNoComments()) {
            return '';
        }
        
        return <<<PHP

/**
 * Class {$definitions['model']['name']}
 *
 * This class represents a {$definitions['model']['name']} object.
 * It extends the {$definitions['abstract']['name']} class and implements the {$definitions['modelInterface']['name']}.
 */
PHP;
    }
    
    /**
     * Generates a string containing validation items for each column in the provided array.
     *
     * @param array $columns An array of ColumnInterface objects.
     * @param array $indexes An array of IndexInterface objects.
     *
     * @return string The generated validation items string.
     */
    public function getValidationItems(array $columns, array $indexes): string
    {
        if ($this->isNoValidations()) {
            return '';
        }
        
        $validationItems = [];
        
        foreach ($indexes as $index) {
            assert($index instanceof IndexInterface);
            $indexType = $index->getType();
            $indexName = $index->getName();
            $indexColumns = $index->getColumns();
            
            if ($indexType === 'UNIQUE' || $indexType === 'PRIMARY') {
                $propertiesName = [];
                foreach ($indexColumns as $indexColumn) {
                    $propertiesName [] = $this->getPropertyName($indexColumn);
                }
                $propertyName = "'" . implode("', '", $propertiesName) . "'";
                $validationItems [] = <<<PHP
        \$this->addUniquenessValidation(\$validator, [{$propertyName}], true); // $indexName
PHP;
            }
        }
        
        foreach ($columns as $column) {
            assert($column instanceof ColumnInterface);
            $columnType = $column->getType();
            $columnName = $column->getName();
            
            $propertyType = $this->getColumnType($column);
            $propertyName = $this->getPropertyName($columnName);
            
            $minSize = 0;
            $maxSize = is_int($column->getSize()) ? $column->getSize() : 0;
            
            $allowEmpty = $column->isNotNull() && !$column->isAutoIncrement() ? 'false' : 'true';
            
            if ($columnType === Column::TYPE_DATE) {
                $validationItems [] = <<<PHP
        \$this->addDateValidation(\$validator, '{$propertyName}', {$allowEmpty});
PHP;
            }
            
            if ($columnType === Column::TYPE_JSON) {
                $validationItems [] = <<<PHP
        \$this->addJsonValidation(\$validator, '{$propertyName}', {$allowEmpty});
PHP;
            }
            
            if ($columnType === Column::TYPE_DATETIME) {
                $validationItems [] = <<<PHP
        \$this->addDateTimeValidation(\$validator, '{$propertyName}', {$allowEmpty});
PHP;
            }
            
            if ($columnType === Column::TYPE_ENUM) {
                $enumValues = $column->getSize();
                $validationItems [] = <<<PHP
        \$this->addInclusionInValidation(\$validator, '{$propertyName}', [{$enumValues}], {$allowEmpty});
PHP;
            }
            
            // String
            if ($propertyType === 'string' && $maxSize) {
                $validationItems [] = <<<PHP
        \$this->addStringLengthValidation(\$validator, '{$propertyName}', {$minSize}, {$maxSize}, {$allowEmpty});
PHP;
            }
            
            // Int
            if ($propertyType === 'int' && $column->isUnsigned()) {
                $validationItems [] = <<<PHP
        \$this->addUnsignedIntValidation(\$validator, '{$propertyName}', {$allowEmpty});
PHP;
            }
        }
        return trim(implode("\n", $validationItems));
    }
    
    /**
     * Generates relationship items for a given table.
     *
     * @param string $table The name of the table.
     * @param array $columns The array of column objects.
     * @param array $tables The array of table names.
     *
     * @return array An array containing the generated relationship items.
     */
    public function getRelationshipItems(string $table, array $columns, array $tables): array
    {
        if ($this->isNoRelationships()) {
            return [
                ' *',
                '',
                ''
            ];
        }
        
        $modelNamespace = $this->getNamespace() . '\\Models\\';
        
        $useModels = [];
        $relationshipUseItems = [];
        $relationshipItems = [];
        $relationshipInjectableItems = [];
        
        $interfaceInjectableItems = [];
        $interfaceUseItems = [];
        $useInterfaces = [];
            
        // Has Many
        foreach ($tables as $otherTable) {
            // skip the current table
            if ($otherTable === $table) {
                continue;
            }
            
            $otherTableColumns = $this->describeColumns($otherTable);
            $relationName = $this->getTableName($otherTable);
            $relationClass = $relationName . '::class';
            $relationAlias = $relationName . 'List';
            $relationEager = strtolower($relationAlias);
            $relationInterface = $relationName . 'AbstractInterface';
            
            // foreach columns of that other table
            foreach ($otherTableColumns as $otherTableColumn) {
                $otherColumnName = $otherTableColumn->getName();
                $otherPropertyName = $this->getPropertyName($otherColumnName);
                
                // if the column name starts with the current table name
                if (str_starts_with($otherColumnName, $table . '_')) {
                    // foreach column of the current table
                    foreach ($columns as $column) {
                        assert($column instanceof ColumnInterface);
                        $columnName = $column->getName();
                        
                        // if the field is matching
                        if ($otherColumnName === $table . '_' . $columnName) {
                            $propertyName = $this->getPropertyName($columnName);
                            
                            $useInterfaces[$relationInterface] = true;
                            $interfaceInjectableItems [] = <<<PHP
 * @property {$relationInterface}[] \${$relationEager}
 * @property {$relationInterface}[] \${$relationAlias}
 * @method {$relationInterface}[] get{$relationAlias}(?array \$params = null)
PHP;
                            
                            $useModels[$relationName] = true;
                            $relationshipInjectableItems [] = <<<PHP
 * @property {$relationName}[] \${$relationEager}
 * @property {$relationName}[] \${$relationAlias}
 * @method {$relationName}[] get{$relationAlias}(?array \$params = null)
PHP;
                            
                            $relationshipItems [] = <<<PHP
        \$this->hasMany('{$propertyName}', {$relationClass}, '{$otherPropertyName}', ['alias' => '{$relationAlias}']);
PHP;
                            // check if we have many-to-many
                            foreach ($otherTableColumns as $manyTableColumn) {
                                assert($manyTableColumn instanceof ColumnInterface);
                                $manyColumnName = $manyTableColumn->getName();
                                $manyPropertyName = $this->getPropertyName($manyColumnName);
                                
                                // skip itself
                                if ($manyColumnName === $otherColumnName) {
                                    continue;
                                }
                                
                                foreach ($tables as $manyManyTable) {
                                    $manyManyTableName = $this->getTableName($manyManyTable);
                                    $manyManyTableInterface = $manyManyTableName . 'AbstractInterface';
                                    $manyManyTableClass = $manyManyTableName . '::class';
                                    $manyManyTableAlias = $manyManyTableName . 'List';
                                    
                                    // to prevent duplicates in this specific scenario when we find many-to-many relationships
                                    // that are not actually nodes, we will enforce the full many-to-many path alias
                                    if (!(str_starts_with($otherTable, $table . '_') || str_ends_with($otherTable, '_' . $table))) {
                                        $manyManyTableAlias = $relationName . $manyManyTableName . 'List';
                                    }
                                    
                                    $manyManyTableEager = strtolower($manyManyTableAlias);
                                    
                                    if (str_starts_with($manyColumnName, $manyManyTable . '_')) {
                                        $manyManyTableColumns = $this->describeColumns($manyManyTable);
                                        foreach ($manyManyTableColumns as $manyManyTableColumn) {
                                            $manyManyColumnName = $manyManyTableColumn->getName();
                                            if ($manyColumnName === $manyManyTable . '_' . $manyManyColumnName) {
                                                $manyManyPropertyName = $this->getPropertyName($manyManyColumnName);
                                                
                                                $useInterfaces[$manyManyTableInterface] = true;
                                                $interfaceInjectableItems [] = <<<PHP
 * @property {$manyManyTableInterface}[] \${$manyManyTableEager}
 * @property {$manyManyTableInterface}[] \${$manyManyTableAlias}
 * @method {$manyManyTableInterface}[] get{$manyManyTableAlias}(?array \$params = null)
PHP;
                                                
                                                $useModels[$manyManyTableName] = true;
                                                $relationshipInjectableItems [] = <<<PHP
 * @property {$manyManyTableName}[] \${$manyManyTableEager}
 * @property {$manyManyTableName}[] \${$manyManyTableAlias}
 * @method {$manyManyTableName}[] get{$manyManyTableAlias}(?array \$params = null)
PHP;
                                                
                                                $relationshipItems [] = <<<PHP
        \$this->hasManyToMany(
            '{$propertyName}',
            {$relationClass},
            '{$otherPropertyName}',
            '{$manyPropertyName}',
            {$manyManyTableClass},
            '{$manyManyPropertyName}',
            ['alias' => '{$manyManyTableAlias}']
        );
PHP;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Belongs To
        foreach ($columns as $column) {
            assert($column instanceof ColumnInterface);
            $columnName = $column->getName();
            $propertyName = $this->getPropertyName($columnName);
            
            $relationName = $this->getTableName(substr($columnName, 0, strlen($columnName) - 3));
            $relationTableName = $relationName;
            
            if (str_ends_with($columnName, '_id')) {
                switch ($relationName) {
                    case 'Parent':
                    case 'Child':
                    case 'Left':
                    case 'Right':
                        $relationTableName = $this->getTableName($table);
                        if (str_contains($table, '_')) {
                            $length = strlen($relationTableName);
                            $midpoint = (int)floor($length / 2);
                            $firstPart = substr($relationTableName, 0, $midpoint);
                            $secondPart = substr($relationTableName, $midpoint + (($length % 2 === 0) ? 0 : 1));
                            if ($firstPart === $secondPart) {
                                $relationTableName = $firstPart;
                            }
                        }
                        
                        break;
                }
            }
            
            if (str_ends_with($columnName, '_as') || str_ends_with($columnName, '_by')) {
                $relationName = $this->getTableName($columnName);
                $relationTableName = 'user';
            }
            
            if ($relationName) {
                $relationTableNameUncamelized = Helper::uncamelize($relationTableName);
                while (!empty($relationTableNameUncamelized) && !in_array($relationTableNameUncamelized, $tables, true)) {
                    $index = strpos($relationTableNameUncamelized, '_');
                    $relationTableNameUncamelized = $index ? substr($relationTableNameUncamelized, $index + 1, strlen($relationTableNameUncamelized)) : null;
                }
                
                // can't find the table, skip
                if (empty($relationTableNameUncamelized)) {
                    continue;
                }
                
                $relationTableName = $this->getTableName($relationTableNameUncamelized);
                $relationTableInterface = $relationTableName . 'AbstractInterface';
                $relationClass = $relationTableName . '::class';
                $relationAlias = $relationName . 'Entity';
                $relationEager = strtolower($relationAlias);
                
                $useInterfaces[$relationTableInterface] = true;
                $interfaceInjectableItems [] = <<<PHP
 * @property {$relationTableInterface} \${$relationEager}
 * @property {$relationTableInterface} \${$relationAlias}
 * @method {$relationTableInterface} get{$relationAlias}(?array \$params = null)
PHP;
                
                $useModels[$relationTableName] = true;
                $relationshipInjectableItems [] = <<<PHP
 * @property {$relationTableName} \${$relationEager}
 * @property {$relationTableName} \${$relationAlias}
 * @method {$relationTableName} get{$relationAlias}(?array \$params = null)
PHP;
                
                $relationshipItems [] = <<<PHP
        \$this->belongsTo('{$propertyName}', {$relationClass}, 'id', ['alias' => '{$relationAlias}']);
PHP;
            }
        }
        
        foreach (array_keys($useModels) as $useItem) {
            $relationshipUseItems [] = 'use ' . $modelNamespace . $useItem . ';';
        }
        foreach (array_keys($useInterfaces) as $useInterface) {
            $interfaceUseItems [] = 'use ' . $modelNamespace . $useInterface . ';';
        }
        
        // Avoid empty lines if not relationship were found
        if (empty($relationshipInjectableItems)) {
            $relationshipInjectableItems = [' * '];
        }
        if (empty($relationshipItems)) {
            $relationshipItems = ['// no default relationship found'];
        }
        
        return [
            'interfaceInjectableItems' => implode("\n" . ' *' . "\n", $interfaceInjectableItems),
            'injectableItems' => implode("\n" . ' *' . "\n", $relationshipInjectableItems),
            'useItems' => trim(implode("\n", $relationshipUseItems)),
            'interfaceUseItems' => trim(implode("\n", $interfaceUseItems)),
            'items' => trim(implode("\n" . "\n", $relationshipItems)),
        ];
    }
    
    public function getColumnMapMethod(array $columns): string
    {
        if ($this->isNoColumnMap()) {
            return '';
        }
        
        $columnMapItems = $this->getColumnMapItems($columns);
        $columnMapComment = $this->getColumnMapComment();
        return <<<PHP
    {$columnMapComment}
    public function columnMap(): array
    {
        return [
{$columnMapItems}
        ];
    }
PHP;
    }
    
    /**
     * Returns the documentation comment for the `getColumnMap` method.
     *
     * @return string The documentation comment for the `getColumnMap` method.
     */
    public function getColumnMapComment(): string
    {
        if ($this->isNoComments()) {
            return '';
        }
        
        return <<<PHP

    /**
     * Returns an array that maps the column names of the database
     * table to the corresponding property names of the model.
     *
     * @returns array The array mapping the column names to the property names
     */
PHP;
    }
    
    /**
     * Generates a string representation of column map items for a given array of columns.
     *
     * @param array $columns An array of columns.
     *
     * @return string The string representation of the column map items.
     */
    public function getColumnMapItems(array $columns): string
    {
        $columnMapItems = [];
        foreach ($columns as $column) {
            assert($column instanceof ColumnInterface);
            $columnName = $column->getName();
            $columnMap = $this->getPropertyName($columnName);
            $columnMapItems[] = <<<PHP
            '{$columnName}' => '{$columnMap}',
PHP;
        }
        return implode("\n", $columnMapItems);
    }
    
    /**
     * Generates property items for each column in the given array.
     *
     * @param array $columns An array of ColumnInterface objects.
     *
     * @return string The generated property items.
     */
    public function getPropertyItems(array $columns): string
    {
        $propertyItems = [];
        foreach ($columns as $column) {
            assert($column instanceof ColumnInterface);
            $definition = $this->getPropertyDefinitions($column);
            $propertyComment = $this->getPropertyComment($column, $definition);
            $propertyItems[] = <<<PHP
    {$propertyComment}
    {$definition['visibility']} {$definition['property']};
PHP;
        }
        
        return trim(implode("\n", $propertyItems));
    }
    
    /**
     * Generates the comment for a property with the given column name and property type.
     *
     * @param ColumnInterface $column The column object.
     * @param array $definitions The property definitions.
     *
     * @return string The generated property comment.
     */
    public function getPropertyComment(ColumnInterface $column, array $definitions): string
    {
        if ($this->isNoComments()) {
            return '';
        }
        $propertyType = $definitions['type'] ?: 'mixed';
        return <<<PHP
    
    /**
     * Column: {$definitions['columnName']}
     * Attributes: {$this->getColumnAttributes($column)}
     * @var {$propertyType}
     */
PHP;
    }
    
    /**
     * Generates a string representation of getters and setters for a given array of columns.
     *
     * @param array $columns An array of columns.
     * @param string $type (optional) The type of code to generate. Can be 'default', 'interface', or 'test'. Default is 'default'.
     * @param string $property (optional) The name of the property to use in setter methods. Default is 'model'.
     *
     * @return string The string representation of the getters and setters.
     */
    public function getGetSetMethods(array $columns, string $type = 'default', string $property = 'model'): string
    {
        $propertyItems = [];
        foreach ($columns as $column) {
            assert($column instanceof ColumnInterface);
            $definition = $this->getPropertyDefinitions($column);
            
            $getMethod = 'get' . ucfirst($definition['name']);
            $setMethod = 'set' . ucfirst($definition['name']);
            
            $testGetMethod = 'test' . ucfirst($getMethod);
            $testSetMethod = 'test' . ucfirst($setMethod);
            $defaultValue = $definition['defaultValue'] ?: 'null';
            
            $getMethodComments = $this->getSetMethodComment($column, $definition, true);
            $setMethodComments = $this->getSetMethodComment($column, $definition, false);
            
            if (!$this->isNoTypings()) {
                $propertyType = isset($definition['type']) ? ': ' . $definition['type'] : '';
                $voidType = ': void';
            } else {
                $propertyType = '';
                $voidType = '';
            }
            
            // For Model
            if ($type === 'default') {
                $propertyItems[] = <<<PHP
    {$getMethodComments}
    public function {$getMethod}(){$propertyType}
    {
        return \$this->{$definition['name']};
    }
    {$setMethodComments}
    public function {$setMethod}({$definition['param']}){$voidType}
    {
        \$this->{$definition['name']} = \${$definition['name']};
    }
PHP;
            }
            
            // For Interface
            if ($type === 'interface') {
                $propertyItems[] = <<<PHP
    {$getMethodComments}
    public function {$getMethod}(){$propertyType};
    {$setMethodComments}
    public function {$setMethod}({$definition['param']}){$voidType};
PHP;
            }
            
            // For Tests
            if ($type === 'test') {
                $propertyItems[] = <<<PHP

    public function {$testGetMethod}(){$voidType}
    {
        \$this->assertEquals({$defaultValue}, \$this->{$property}->{$getMethod}());
    }
    
    public function {$testSetMethod}(){$voidType}
    {
        \$value = uniqid();
        \$this->{$property}->{$setMethod}(\$value);
        \$this->assertEquals(\$value, \$this->{$property}->{$getMethod}());
    }
PHP;
            }
        }
        return trim(implode("\n", $propertyItems));
    }
    
    /**
     * Generates a comment for a getter or setter method for a specific column.
     *
     * @param ColumnInterface $column The column object.
     * @param array $definitions The property definitions.
     * @param bool $get Determines whether the comment is for a getter or setter method.
     *
     * @return string The generated comment.
     */
    public function getSetMethodComment(ColumnInterface $column, array $definitions, bool $get): string
    {
        if ($this->isNoComments()) {
            return '';
        }
        
        $propertyType = $definitions['type'] ?: 'mixed';
        
        if ($get) {
            return <<<PHP

    /**
     * Returns the value of the field "{$definitions['name']}"
     * Column: {$definitions['columnName']}
     * Attributes: {$this->getColumnAttributes($column)}
     * @return {$propertyType}
     */
PHP;
        }
        
        else {
            return <<<PHP

    /**
     * Sets the value of the field "{$definitions['name']}"
     * Column: {$definitions['columnName']}
     * Attributes: {$this->getColumnAttributes($column)}
     * @param {$propertyType} \${$definitions['name']}
     * @return void
     */
PHP;
        }
    }
    
    public function getColumnAttributes(ColumnInterface $column): string
    {
        $attributes = [];
        if ($column->isFirst()) {
            $attributes [] = 'First';
        }
        if ($column->isPrimary()) {
            $attributes [] = 'Primary';
        }
        if ($column->isNotNull()) {
            $attributes [] = 'NotNull';
        }
        if ($column->isNumeric()) {
            $attributes [] = 'Numeric';
        }
        if ($column->isUnsigned()) {
            $attributes [] = 'Unsigned';
        }
        if ($column->isAutoIncrement()) {
            $attributes [] = 'AutoIncrement';
        }
        if ($column->getSize()) {
            $attributes [] = 'Size(' . $column->getSize() . ')';
        }
        if ($column->getScale()) {
            $attributes [] = 'Scale(' . $column->getSize() . ')';
        }
        if ($column->getType()) {
            $attributes [] = 'Type(' . $column->getType() . ')';
        }
        return implode(' | ', $attributes);
    }
    
    public function getPropertyDefinitions(ColumnInterface $column): array
    {
        // column
        $columnName = $column->getName();
        $columnType = $this->getColumnType($column);
        $defaultValue = $this->getDefaultValue($column);
        $optional = !$column->isNotNull() || $column->isAutoIncrement() || is_null($defaultValue);
        
        // property
        $propertyVisibility = $this->isProtectedProperties() ? 'protected' : 'public';
        $propertyName = $this->getPropertyName($column->getName());
        
        // property type
        $propertyType = $this->isNoTypings() ? '' : 'mixed';
        if ($this->isGranularTypings()) {
            $rawValueType = $this->isAddRawValueType() ? 'RawValue|' : '';
            $nullType = $optional ? '|null' : '';
            $propertyType = $rawValueType . $columnType . $nullType;
        }
        
        // property raw value
        $propertyValue = isset($defaultValue) ? ' = ' . $defaultValue : '';
        if (empty($propertyValue) && $optional) {
            $propertyValue = ' = null';
        }
        
        $param = (empty($propertyType) ? '' : $propertyType . ' ') . "\${$propertyName}";
        $property = "{$param}{$propertyValue}";
        
        return [
            'columnName' => $columnName,
            'columnType' => $columnType,
            'defaultValue' => $defaultValue,
            'optional' => $optional,
            'visibility' => $propertyVisibility,
            'name' => $propertyName,
            'type' => $propertyType,
            'value' => $propertyValue,
            'param' => $param,
            'property' => $property,
        ];
    }
    
    /**
     * Saves a file with the given text content.
     *
     * @param string $file The path of the file to be saved.
     * @param string $text The content to be written to the file.
     * @param bool $force Determines whether to overwrite an existing file. Default is false.
     *
     * @return bool Returns true if the file was saved successfully, false otherwise.
     */
    public function saveFile(string $file, string $text, bool $force = false): bool
    {
        if (!$force && file_exists($file)) {
            return false;
        }
        
        $directory = dirname($file);
        
        // Create the directory if it doesn't exist
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false; // Failed to create directory
        }
        
        // Convert text to UTF-8
        $utf8Text = mb_convert_encoding($text, 'UTF-8');
        if ($utf8Text === false) {
            return false; // Failed to convert to UTF-8
        }
        
        // Optional: Add UTF-8 BOM
//        $utf8Text = "\xEF\xBB\xBF" . $utf8Text;
        
        // Write the file
        $fileHandle = fopen($file, 'w');
        if ($fileHandle === false) {
            return false; // Failed to open file
        }
        
        $writeSuccess = fwrite($fileHandle, $utf8Text) !== false;
        $closeSuccess = fclose($fileHandle);
        
        return $writeSuccess && $closeSuccess;
    }
}
