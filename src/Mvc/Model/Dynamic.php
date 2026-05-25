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

namespace PhalconKit\Mvc\Model;

use Phalcon\Cache\Adapter\Apcu;
use Phalcon\Mvc\Model\MetaData;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Model;
use PhalconKit\Support\Utils;

/**
 * Runtime model whose source and metadata can change per instance.
 *
 * Dynamic models currently invalidate known APCu metadata entries during
 * initialization because the native metadata manager does not expose a stable
 * per-model reset API for this use case.
 */
class Dynamic extends Model
{
    protected array $_metaData = [];
    protected array $_columnMap = [];
    
    #[\Override]
    public function initialize(): void
    {
        $this->setConnectionService('dbd');
        $this->setReadConnectionService('dbd');
        $this->setWriteConnectionService('dbd');
        
        // Force-delete cache entries for dynamic models. A custom metadata
        // strategy or adapter wrapper would be cleaner, but would need to keep
        // native Phalcon metadata behavior compatible for normal models.
        $modelsMetaData = $this->requireDynamicMetaData($this->getModelsMetaData());
        $adapter = $modelsMetaData->getAdapter();
        if ($adapter instanceof Apcu) {
            $lowerClassName = strtolower(Utils::getName($this));
            $adapter->delete('meta-' . $lowerClassName);
            $adapter->delete('map-' . $lowerClassName);
        }
        
        parent::initialize();
    }
    
    /**
     * Set the source table dynamically.
     */
    public function setDynamicSource(string $table): void
    {
        $this->setSource($table);
    }

    /**
     * Require native Phalcon metadata for dynamic model cache invalidation.
     *
     * Dynamic models clear APCu metadata entries by reaching into the native
     * metadata adapter. If an application replaces the metadata service with an
     * incompatible implementation, this helper fails early with a PhalconKit
     * logic exception instead of relying on disabled assertions or late method
     * errors.
     *
     * @param mixed $modelsMetaData Metadata service returned by Phalcon.
     *
     * @return MetaData
     *
     * @throws LogicException When the metadata service is incompatible with
     *     dynamic model cache invalidation.
     */
    protected function requireDynamicMetaData(mixed $modelsMetaData): MetaData
    {
        if ($modelsMetaData instanceof MetaData) {
            return $modelsMetaData;
        }

        throw new LogicException(sprintf(
            'Dynamic models require "%s" metadata; got "%s".',
            MetaData::class,
            get_debug_type($modelsMetaData)
        ));
    }
    
    /**
     * Sets dynamic metadata for the object.
     */
    public function setDynamicMetaData(array $metaData): void
    {
        $this->_metaData = $metaData;
    }
    
    /**
     * Set the column mapping dynamically.
     */
    public function setDynamicColumnMap(array $map): void
    {
        $this->_columnMap = $map;
    }
    
    /**
     * Dynamically set the column mapping.
     * Phalcon uses this to map database columns to model attributes.
     */
    public function columnMap(): array
    {
        if (empty($this->_columnMap)) {
            // Dynamically load column names from the database (or handle them dynamically)
            $metadata = $this->getModelsMetaData();
            $dataTypes = $metadata->getDataTypes($this);
            $columnMap = array_keys($dataTypes);
            
            // Store and return the dynamically generated column map
            $this->_columnMap = array_combine($columnMap, $columnMap);
        }
        
        return $this->_columnMap;
    }
    
    /**
     * Create a dynamic instance with a specific source and column map.
     */
    public static function createInstance(string $source, array $columnMap = []): Dynamic
    {
        $instance = new self();
        $instance->setDynamicSource($source);
        if (!empty($columnMap)) {
            $instance->setDynamicColumnMap($columnMap);
        }
        return $instance;
    }
}
