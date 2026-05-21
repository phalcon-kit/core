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

use Phalcon\Messages\MessageInterface;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Mvc\Model;

class NativeRelationshipModelDouble extends Model
{
    public mixed $id = null;
    public mixed $parentId = null;
    public mixed $name = null;

    public array $attributes = [];
    public array $messages = [];
    public ?FakeModelsManager $fakeModelsManager = null;
    public ?MetaDataInterface $fakeModelsMetaData = null;

    #[\Override]
    public function initialize(): void
    {
        $this->setSource('native_relationship_model_double');
    }

    #[\Override]
    public function readAttribute(string $attribute)
    {
        return property_exists($this, $attribute) ? $this->{$attribute} : ($this->attributes[$attribute] ?? null);
    }

    #[\Override]
    public function writeAttribute(string $attribute, mixed $value): void
    {
        if (property_exists($this, $attribute)) {
            $this->{$attribute} = $value;
            return;
        }

        $this->attributes[$attribute] = $value;
    }

    #[\Override]
    public function appendMessage(MessageInterface $message): ModelInterface
    {
        $this->messages[] = $message;
        return $this;
    }

    #[\Override]
    public function getMessages($filter = null): array
    {
        return $this->messages;
    }

    #[\Override]
    public function getModelsManager(): \Phalcon\Mvc\Model\ManagerInterface
    {
        return $this->fakeModelsManager ?? parent::getModelsManager();
    }

    #[\Override]
    public function getModelsMetaData(): MetaDataInterface
    {
        return $this->fakeModelsMetaData ?? parent::getModelsMetaData();
    }
}
