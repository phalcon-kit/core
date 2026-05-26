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

namespace PhalconKit\Mvc\Model\Traits\Abstracts;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Exception\LogicException;

trait AbstractMetaData
{
    use AbstractInjectable;
    
    abstract public function getModelsMetaData(): MetaDataInterface;

    /**
     * Require the trait host to satisfy Phalcon's model metadata contract.
     *
     * Metadata helpers call native model-manager APIs that expect a model
     * instance. This explicit check keeps accidental composition into a
     * non-model class from falling through to PHP assertions or late method
     * errors.
     *
     * @throws LogicException When the trait host is not a Phalcon model.
     */
    protected function requireMetaDataModel(): ModelInterface
    {
        if ($this instanceof ModelInterface) {
            return $this;
        }

        throw new LogicException(sprintf(
            'Model metadata helpers require the trait host to implement "%s"; got "%s".',
            ModelInterface::class,
            get_debug_type($this)
        ));
    }

    /**
     * Require the trait host to expose Phalcon's entity attribute API.
     *
     * Primary-key value and attribute helpers read and write raw model
     * attributes. This helper gives extension authors a deterministic framework
     * exception when that entity API is unavailable.
     *
     * @throws LogicException When the trait host is not a Phalcon entity.
     */
    protected function requireMetaDataEntity(): EntityInterface
    {
        if ($this instanceof EntityInterface) {
            return $this;
        }

        throw new LogicException(sprintf(
            'Model metadata helpers require the trait host to implement "%s"; got "%s".',
            EntityInterface::class,
            get_debug_type($this)
        ));
    }
}
