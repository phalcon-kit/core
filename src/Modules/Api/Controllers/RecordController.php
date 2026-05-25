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

namespace PhalconKit\Modules\Api\Controllers;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Support\Collection;
use PhalconKit\Models\Table;
use PhalconKit\Modules\Api\Controller;
use PhalconKit\Mvc\Model\Dynamic;

class RecordController extends Controller
{
    protected ?int $limit = 10000;
    
    protected ?int $maxLimit = 10000;
    
    protected string $source = 'dynamic';
    
    protected ?Collection $columnMap = null;
    
    protected array $metaData = [];
    
    /**
     * @return void
     */
    #[\Override]
    public function initialize()
    {
        $this->initializeSource();
        $this->initializeTableColumns();
        
        parent::initialize();
    }
    
    public function initializeSource(): void
    {
        $this->source = $this->getParam('advanced')['tableUuid'] ?? 'dynamic';
    }
    
    public function initializeTableColumns(): void
    {
        $this->columnMap = new Collection([
            'id' => 'id',
            'uuid' => 'uuid',
            'deleted' => 'deleted',
        ]);
        
        $table = Table::findFirst([
            'uuid = :uuid:',
            'bind' => ['uuid' => $this->getSource()],
        ]);
        if ($table instanceof Table) {
            $columns = $table->getColumnList();
            foreach ($columns as $column) {
                $this->columnMap->set($column->getUuid(), $column->getUuid());
            }
        }
    }
    
    #[\Override]
    public function listExpose(iterable $items, ?array $expose = null): array
    {
        return (array)$items;
    }
    
    #[\Override]
    public function initializeSearchFields(): void
    {
        $this->setSearchFields($this->columnMap);
    }
    
    #[\Override]
    public function initializeFilterFields(): void
    {
        $this->setFilterFields(new Collection([
            'workspaceId',
            'tableId',
        ]));
    }
    
    public function hasAdvanced(string $key): bool
    {
        $advanced = $this->getParam('advanced') ?? [];
        return isset($advanced[$key]);
    }
    
    #[\Override]
    public function getModelName(): ?string
    {
        if (!isset($this->modelName)) {
            $this->modelName = Dynamic::class;
        }
        
        return $this->modelName;
    }
    
    #[\Override]
    public function loadModel(?string $modelName = null): ModelInterface
    {
        $modelName ??= $this->getModelName() ?? '';
        $modelInstance = Dynamic::createInstance($this->getSource(), $this->getColumnMap());
        $modelInstance->setDynamicSource($this->getSource());
        $modelInstance->setDynamicMetaData($this->getMetaData());
        $modelInstance->setDynamicColumnMap($this->getColumnMap());
        return $modelInstance;
    }
    
    public function getColumnMap(): array
    {
        return $this->columnMap?->toArray() ?? [];
    }
    
    public function getSource(): string
    {
        return $this->source;
    }
    
    public function getMetaData(): array
    {
        /**
         * Initialize meta-data
         */
        $attributes = [];
        $primaryKeys = [];
        $nonPrimaryKeys = [];
        $numericTyped = [];
        $notNull = [];
        $fieldTypes = [];
        $fieldBindTypes = [];
        $automaticDefault = [];
        $identityField = false;
        $defaultValues = [];
        $emptyStringValues = [];
        
        $columns = $this->dbd->describeColumns($this->getSource());
        foreach ($columns as $column) {
            $fieldName = $column->getName();
            $attributes[] = $fieldName;
            
            /**
             * To mark fields as primary keys
             */
            if ($column->isPrimary()) {
                $primaryKeys[] = $fieldName;
            }
            else {
                $nonPrimaryKeys[] = $fieldName;
            }
            
            /**
             * To mark fields as numeric
             */
            if ($column->isNumeric()) {
                $numericTyped[$fieldName] = true;
            }
            
            /**
             * To mark fields as not null
             */
            if ($column->isNotNull()) {
                $notNull[] = $fieldName;
            }
            
            /**
             * To mark fields as identity $columns
             */
            if ($column->isAutoIncrement()) {
                $identityField = $fieldName;
            }
            
            /**
             * To get the internal types
             */
            $fieldTypes[$fieldName] = $column->getType();
            
            /**
             * To mark how the fields must be escaped
             */
            $fieldBindTypes[$fieldName] = $column->getBindType();
            
            /**
             * If $column has default value or $column is nullable and default value is null
             */
            $defaultValue = $column->getDefault();
            
            if ($defaultValue !== null || !$column->isNotNull()) {
                if (!$column->isAutoIncrement()) {
                    $defaultValues[$fieldName] = $defaultValue;
                }
            }
        }
        
        /**
         * Create an array using the MODELS_* constants as indexes
         */
        return [
            MetaData::MODELS_ATTRIBUTES => $attributes,
            MetaData::MODELS_PRIMARY_KEY => $primaryKeys,
            MetaData::MODELS_NON_PRIMARY_KEY => $nonPrimaryKeys,
            MetaData::MODELS_NOT_NULL => $notNull,
            MetaData::MODELS_DATA_TYPES => $fieldTypes,
            MetaData::MODELS_DATA_TYPES_NUMERIC => $numericTyped,
            MetaData::MODELS_IDENTITY_COLUMN => $identityField,
            MetaData::MODELS_DATA_TYPES_BIND => $fieldBindTypes,
            MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => $automaticDefault,
            MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => $automaticDefault,
            MetaData::MODELS_DEFAULT_VALUES => $defaultValues,
            MetaData::MODELS_EMPTY_STRING_VALUES => $emptyStringValues,
        ];
    }
}
