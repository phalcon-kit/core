<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Mvc\Model\MetaData\Memory;
use Phalcon\Mvc\ModelInterface;

class FakeMetaData extends Memory
{
    public array $attributes = ['id'];
    public ?array $fakeColumnMap = null;
    public array $dataTypes = ['id' => \Phalcon\Db\Column::TYPE_INTEGER];
    public array $primaryKeyAttributes = ['id'];
    public array $bindTypes = ['id' => \Phalcon\Db\Column::BIND_PARAM_INT];
    public ?array $fakeReverseColumnMap = null;

    #[\Override]
    public function getAttributes(ModelInterface $model): array
    {
        return $this->attributes;
    }

    #[\Override]
    public function getColumnMap(ModelInterface $model): ?array
    {
        return $this->fakeColumnMap;
    }

    #[\Override]
    public function getReverseColumnMap(ModelInterface $model): ?array
    {
        return $this->fakeReverseColumnMap;
    }

    #[\Override]
    public function getDataTypes(ModelInterface $model): array
    {
        return $this->dataTypes;
    }

    #[\Override]
    public function getPrimaryKeyAttributes(ModelInterface $model): array
    {
        return $this->primaryKeyAttributes;
    }

    #[\Override]
    public function getBindTypes(ModelInterface $model): array
    {
        return $this->bindTypes;
    }
}
