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

namespace PhalconKit\Mvc\Model\Traits;

use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractMetaData;

/**
 * The MetaData trait provides methods for retrieving metadata information about a model or entity.
 */
trait MetaData
{
    use AbstractMetaData;
    
    /**
     * Get the column mapping of the model
     *
     * @return array|null The column mapping of the model, or null if no mapping is defined
     */
    public function getColumnMap(): ?array
    {
        $model = $this->requireMetaDataModel();

        return $model->getModelsMetaData()->getColumnMap($model);
    }
    
    /**
     * Retrieves the primary keys attributes of the model.
     *
     * @return array Array containing the primary keys of the model.
     */
    public function getPrimaryKeys(): array
    {
        $model = $this->requireMetaDataModel();

        return $model->getModelsMetaData()->getPrimaryKeyAttributes($model);
    }
    
    /**
     * Retrieves the values of the primary keys attributes of the entity.
     *
     * @return array Array containing the values of the primary keys attributes of the entity.
     */
    public function getPrimaryKeysValues(): array
    {
        $ret = [];
        $columnMap = $this->getColumnMap() ?? [];
        $entity = $this->requireMetaDataEntity();
        
        foreach ($this->getPrimaryKeys() as $primaryKey) {
            $attributeField = $columnMap[$primaryKey] ?? $primaryKey;
            $ret [$attributeField] = $entity->readAttribute($attributeField);
        }
        
        return $ret;
    }
}
