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

namespace PhalconKit\Modules\Cli\Tasks;

use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Mvc\Model\Relation;
use PhalconKit\Modules\Cli\Task;
use PhalconKit\Mvc\Model;
use PhalconKit\Support\Helper;
use PhalconKit\Support\Slug;

class TsScaffoldTask extends Task
{
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli ts-scaffold <action> [<params>...] [--force] [--table=<table>] [--directory=<directory>]

Options:
  --force                       Overwrite existing files
  --directory=<directory>       Directory path to generate new files
  --table=<table>               Comma seperated list of table to generate


DOC;
    
    public string $path = './sdk/';
    public string $servicesPath = 'services/';
    public string $modelsPath = 'models/';
    public string $abstractsPath = 'abstracts/';
    public string $interfacesPath = 'interfaces/';
    
    public function getDefinitionsAction(string $name): array
    {
        $definitions = [];
        
        $definitions['table'] = $this->getTableName($name);
        $definitions['slug'] = Slug::generate(Helper::uncamelize($name));
        
        // backend model
        $definitions['backend'] = 'PhalconKit\\Models\\' . $definitions['table'];
        
        // model
        $definitions['model']['name'] = $definitions['table'] . 'Model';
        $definitions['model']['file'] = $definitions['model']['name'] . '.ts';
        $definitions['model']['export'] = trim($this->modelsPath, '/') . '.ts';
        $definitions['model']['path'] = $this->modelsPath;
        
        // service
        $definitions['service']['name'] = $definitions['table'] . 'Service';
        $definitions['service']['file'] = $definitions['service']['name'] . '.ts';
        $definitions['service']['export'] = trim($this->servicesPath, '/') . '.ts';
        $definitions['service']['path'] = $this->servicesPath;
        
        // service
        $definitions['interface']['name'] = $definitions['table'] . 'ModelInterface';
        $definitions['interface']['file'] = $definitions['interface']['name'] . '.ts';
        $definitions['interface']['export'] = trim($this->interfacesPath, '/') . '.ts';
        $definitions['interface']['path'] = $this->interfacesPath;
        
        // abstract
        $definitions['abstract']['name'] = $definitions['table'] . 'ModelAbstract';
        $definitions['abstract']['file'] = $definitions['abstract']['name'] . '.ts';
        $definitions['abstract']['export'] = trim($this->abstractsPath, '/') . '.ts';
        $definitions['abstract']['path'] = $this->abstractsPath;
        
        
        return $definitions;
    }
    
    public function generateExportsAction(): array
    {
        $ret = [];
        
        $directory = $this->dispatcher->getParam('directory');
        $files = glob($directory . '*.ts') ?: [];
        
        $exports = [];
        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            if ($fileName !== 'index') {
                $exports [] = "export {{$fileName}} from './{$fileName}'";
            }
        }
        
        $interfacesExportPath = $directory . 'index.ts';
        
        $ret ['exports'] = $exports;
        $ret ['filePath'] = $interfacesExportPath;
        $ret ['saved'] = $this->saveFile($interfacesExportPath, implode("\n", $exports));
        
        return $ret;
    }
    
    public function runAction(): array
    {
        $ret = [];
        
        $exports = [
            'models' => [],
            'services' => [],
            'interfaces' => [],
            'abstracts' => [],
        ];
        
        $force = $this->dispatcher->getParam('force') ?? false;
        $whitelisted = array_filter(explode(',', $this->dispatcher->getParam('table') ?? ''));
        $tables = $this->db->listTables();
        foreach ($tables as $table) {
            if (!empty($whitelisted) && !in_array($table, $whitelisted)) {
                continue;
            }
            
            $columns = $this->db->describeColumns($table);
            $definitions = $this->getDefinitionsAction($table);
            $related = $this->getRelatedMeta($definitions['backend']);
            
            // Save Interface File
            $savePath = $this->path . $this->modelsPath . $this->abstractsPath . $this->interfacesPath . $definitions['interface']['file'];
            if (!file_exists($savePath) || $force) {
                $columns = $this->db->describeColumns($table);
                $this->saveFile($savePath, $this->createInterfaceOutput($definitions, $columns), $force);
                $ret [] = 'Interface `' . $definitions['interface']['file'] . '` created';
            }
            
            // Abstract
            $savePath = $this->path . $this->modelsPath . $this->abstractsPath . $definitions['abstract']['file'];
            if (!file_exists($savePath) || $force) {
                $this->saveFile($savePath, $this->createAbstractOutput($definitions, $columns), $force);
                $ret [] = 'Abstract `' . $definitions['abstract']['file'] . '` created';
            }
            
            // Model
            $savePath = $this->path . $this->modelsPath . $definitions['model']['file'];
            if (!file_exists($savePath) || $force) {
                $this->saveFile($savePath, $this->createModelOutput($definitions, $related), $force);
                $ret [] = 'Model ' . $definitions['model']['file'] . ' created';
            }
            
            // Create Service
            $savePath = $this->path . $this->servicesPath . $definitions['service']['file'];
            if (!file_exists($savePath) || $force) {
                $this->saveFile($savePath, $this->createServiceOutput($definitions), $force);
                $ret [] = 'Service `' . $definitions['service']['file'] . '` created';
            }
            
            $this->appendExport($definitions, $exports);
        }
        
        // interfaces
        $exportDirectory = $this->path . $this->modelsPath . $this->abstractsPath . $this->interfacesPath;
        $this->dispatcher->setParam('directory', $exportDirectory);
        $this->generateExportsAction();
        $ret [] = 'Interfaces Export `index.ts` created';
        
        // abstracts
        $exportDirectory = $this->path . $this->modelsPath . $this->abstractsPath;
        $this->dispatcher->setParam('directory', $exportDirectory);
        $this->generateExportsAction();
        $ret [] = 'Abstracts Export `index.ts` created';
        
        // models
        $exportDirectory = $this->path . $this->modelsPath;
        $this->dispatcher->setParam('directory', $exportDirectory);
        $this->generateExportsAction();
        $ret [] = 'Models Export `index.ts` created';
        
        // services
        $exportDirectory = $this->path . $this->servicesPath;
        $this->dispatcher->setParam('directory', $exportDirectory);
        $this->generateExportsAction();
        $ret [] = 'Services Export `index.ts` created';
        
        return $ret;
    }
    
    public function appendExport(array $definitions, array &$exports): void
    {
        $exports['models'] [] = "export {{$definitions['model']['name']}} from './{$definitions['model']['name']}'";
        $exports['interfaces'] [] = "export {{$definitions['interface']['name']}} from './{$definitions['interface']['name']}'";
        $exports['services'] [] = "export {{$definitions['service']['name']}} from './{$definitions['service']['name']}'";
        $exports['abstracts'] [] = "export {{$definitions['abstract']['name']}} from './{$definitions['abstract']['name']}'";
    }
    
    public function createInterfaceOutput(array $definitions, array $columns): string
    {
        $propertyItems = str_replace('!: ', ': ', $this->getPropertyItems($columns));
        return <<<EOT
export interface {$definitions['interface']['name']} {
{$propertyItems}
}
EOT;
    }
    
    /**
     * Creates a typescript abstract model output based on the given definitions.
     */
    public function createAbstractOutput(array $definitions, array $columns): string
    {
        $from = './' . $this->interfacesPath . $definitions['interface']['name'];
        $propertyItems = $this->getPropertyItems($columns);
        return <<<EOT
import { AbstractModel } from '../AbstractModel';
import { {$definitions['interface']['name']} } from '$from';

export class {$definitions['abstract']['name']} extends AbstractModel implements {$definitions['interface']['name']} {
{$propertyItems}
}
EOT;
    }
    
    /**
     * Creates a typescript model output based on the given definitions.
     */
    public function createModelOutput(array $definitions, array $related): string
    {
        $from = './' . $this->abstractsPath . $definitions['abstract']['name'];
        $relatedImportItems = $this->getRelatedImportItems($related);
//        $relatedDefaultItems = $this->getRelatedDefaultItems($related);
        $relatedPropertyItems = $this->getRelatedProperties($related);
        $importTypeClassTransformer = !empty($relatedPropertyItems) ?
            "import { Type } from 'class-transformer';" : '';
        return <<<EOT
import 'reflect-metadata';
{$importTypeClassTransformer}
import { {$definitions['abstract']['name']} } from '$from';
{$relatedImportItems}

export class {$definitions['model']['name']} extends {$definitions['abstract']['name']} {
{$relatedPropertyItems}
}
EOT;
    }
    
    public function createServiceOutput(array $definitions): string
    {
        
        $from = '../' . $this->modelsPath . $definitions['model']['name'];
        return <<<EOT
import { AbstractService } from './AbstractService';
import { {$definitions['model']['name']} } from '$from';

export class {$definitions['service']['name']} extends AbstractService {
    modelUrl = '{$definitions['slug']}';
    model = {$definitions['model']['name']};
}
EOT;
    }
    
    public function getRelatedImportItems(array $related): string
    {
        $relatedImportItems = [];
        if (!empty($related['import'])) {
            foreach ($related['import'] as $key => $value) {
                $relatedImportItems [] = 'import { ' . $key . ' } from \'./' . $key . '\';';
            }
        }
        return implode("\n", $relatedImportItems);
    }
    
    /**
     * Returns a formatted string representation of the related default items.
     */
    public function getRelatedDefaultItems(array $related): string
    {
        $relatedMapItems = [];
        if (!empty($related['default'])) {
            foreach ($related['default'] as $key => $value) {
                $relatedMapItems [] = '  ' . $key . ' = ' . $value . ',';
            }
        }
        return implode("\n", $relatedMapItems);
    }
    
    /**
     * Returns a formatted string representation of the related map items.
     */
    public function getRelatedMapItems(array $related): string
    {
        $relatedMapItems = [];
        if (!empty($related['map'])) {
            foreach ($related['map'] as $key => $value) {
                $relatedMapItems [] = '  ' . $key . '!: ' . $value . ';';
            }
        }
        return implode("\n", $relatedMapItems);
    }
    
    /**
     * Returns a formatted string representation of the related map items.
     */
    public function getRelatedProperties(array $related): string
    {
        $relatedMapItems = [];
        if (!empty($related['data'])) {
            foreach ($related['data'] as $key => $value) {
                $type = str_replace('[]', '', $value);
                assert(is_string($type));
                $relatedMapItems [] = '';
                $relatedMapItems [] = '  @Type(() => ' . $type . ')';
                $relatedMapItems [] = '  ' . $key . '!: ' . $value . ';';
            }
        }
        return implode("\n", $relatedMapItems);
    }
    
    /**
     * Returns a formatted string representation of the property items.
     *
     * @param ColumnInterface[] $columns An array of column objects.
     * @return string A string representation of the property items.
     */
    public function getPropertyItems(array $columns): string
    {
        $propertyItems = [];
        foreach ($columns as $column) {
            $columnName = $this->getColumnName($column->getName());
            $columnType = $this->getColumnTsType($column);
//            $defaultValue = $this->getDefaultValue($column, $columnType);
            $propertyItems[] = "  $columnName!: $columnType;";
        }
        return implode("\n", $propertyItems);
    }
    
    public function getRelatedMeta(string $modelClassName): array
    {
        $related = [
            'import' => [],
            'map' => [],
            'default' => [],
            'data' => [],
        ];
        
        $modelInstance = $this->getModelInstance($modelClassName);
        $modelManager = $modelInstance->getModelsManager();
        $relations = $modelManager->getRelations($modelClassName);
        foreach ($relations as $relation) {
            $relationAlias = $relation->getOption('alias');
            $relationModelName = $this->getModelNameFromClassName($relation->getReferencedModel());
            // do not import itself
            $modelName = basename(str_replace('\\', '/', $modelClassName)) . 'Model';
            if ($relationModelName !== $modelName) {
                // import related model
                $related['import'][$relationModelName] = '';
            }
            // add related map entry
            $related['map'][$relationAlias] = $relationModelName;
            
            if (
                $relation->getType() === Relation::HAS_MANY ||
                $relation->getType() === Relation::HAS_MANY_THROUGH
            ) {
                // set default value to empty array
                $related['default'][$relationAlias] = '[]';
                $related['data'][$relationAlias] = $relationModelName . '[]';
            }
            else {
                $related['data'][$relationAlias] = $relationModelName;
            }
        }
        
        return $related;
    }
    
    public function getColumnTsType(ColumnInterface $column): string
    {
        $tsType = 'null';
        
        if ($column->isNumeric()) {
            $tsType = 'number';
        }
        
        switch ($column->getType()) {
            case Column::TYPE_TIMESTAMP:
            case Column::TYPE_BIGINTEGER:
            case Column::TYPE_MEDIUMINTEGER:
            case Column::TYPE_SMALLINTEGER:
            case Column::TYPE_TINYINTEGER:
            case Column::TYPE_INTEGER:
            case Column::TYPE_DECIMAL:
            case Column::TYPE_DOUBLE:
            case Column::TYPE_FLOAT:
            case Column::TYPE_BIT:
                $tsType = 'number';
                break;
            
            case Column::TYPE_ENUM:
            case Column::TYPE_VARCHAR:
            case Column::TYPE_CHAR:
            case Column::TYPE_TEXT:
            case Column::TYPE_TINYTEXT:
            case Column::TYPE_MEDIUMTEXT:
            case Column::TYPE_BLOB:
            case Column::TYPE_TINYBLOB:
            case Column::TYPE_LONGBLOB:
            case Column::TYPE_DATETIME:
            case Column::TYPE_DATE:
            case Column::TYPE_TIME:
            case Column::TYPE_BINARY:
                $tsType = 'string';
                break;
            
            case Column::TYPE_JSON:
            case Column::TYPE_JSONB:
                $tsType = 'object';
                break;
            
            case Column::TYPE_BOOLEAN:
                $tsType = 'boolean';
                break;
            
            default:
                break;
        }
        
        return $tsType;
    }
    
    public function getDefaultValue(ColumnInterface $column, string $type): ?string
    {
        $default = null;
        $columnDefault = $column->getDefault();
        switch (getType(strtolower($columnDefault ?? ''))) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'null':
                $default = $columnDefault;
                break;
            
            case 'string':
                if ($type === 'string') {
                    $default = !empty($columnDefault) ? '"' . addslashes($columnDefault) . '"' : '';
                }
                if ($type === 'number') {
                    $default = !empty($columnDefault) ? $columnDefault : '';
                }
                if ($type === 'array') {
                    $default = '[]';
                }
                if ($type === 'object') {
                    $default = '{}';
                }
                break;
            
            case 'array':
                $default = '[]';
                break;
            
            case 'object':
                $default = '{}';
                break;
            
            default:
                break;
        }
        
        return $default;
    }
    
    public function getColumnName(string $name): string
    {
        return lcfirst(
            Helper::camelize(
                Helper::uncamelize(
                    $name
                )
            )
        );
    }
    
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
    
    public function getModelInstance(string $modelClassName): Model
    {
        if (class_exists($modelClassName)) {
            $modelInstance = new $modelClassName();
            assert($modelInstance instanceof Model);
            return $modelInstance;
        }
        return new Model();
    }
    
    public function getModelNameFromClassName(string $className): string
    {
        return ucfirst(
            Helper::camelize(
                Helper::uncamelize(
                    basename(
                        str_replace(
                            '\\',
                            '/',
                            $className
                        )
                    )
                )
            )
        ) . 'Model';
    }
    
    public function saveFile(string $file, string $text, bool $force = false): bool
    {
        if (!$force && file_exists($file)) {
            return false;
        }
        
        $directory = dirname($file);
        
        // Create the directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $resource = fopen($file, 'w');
        return $resource && fwrite($resource, $text) && fclose($resource);
    }
}
