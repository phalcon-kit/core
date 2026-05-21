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

namespace PhalconKit\Fractal;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Di\InjectableTrait;
use PhalconKit\Mvc\Model\Interfaces\RelationshipInterface;

/**
 * This class extends the TransformerAbstract class and implements the InjectionAwareInterface.
 * It also uses the InjectableTrait.
 */
class Transformer extends TransformerAbstract implements InjectionAwareInterface
{
    use InjectableTrait;

    protected function includeCollectionIfLoaded(
        ModelInterface $entity,
        string $alias,
        Transformer $transformer
    ): Collection {
        $related = $this->getLoadedRelationAlias($entity, $alias);

        return $this->collection(
            is_iterable($related) ? $related : [],
            $transformer
        );
    }

    protected function includeItemIfLoaded(
        ModelInterface $entity,
        string $alias,
        Transformer $transformer
    ): ?Item {
        if (!$this->isRelationAliasLoaded($entity, $alias)) {
            return null;
        }

        $related = $this->getLoadedRelationAlias($entity, $alias);
        if ($related === null || is_iterable($related)) {
            return null;
        }

        return $this->item($related, $transformer);
    }

    protected function isRelationAliasLoaded(ModelInterface $entity, string $alias): bool
    {
        return $entity instanceof RelationshipInterface
            && ($entity->hasDirtyRelatedAlias($alias) || $entity->hasLoadedRelatedAlias($alias));
    }

    protected function getLoadedRelationAlias(ModelInterface $entity, string $alias): mixed
    {
        if (!$entity instanceof RelationshipInterface) {
            return null;
        }

        if ($entity->hasLoadedRelatedAlias($alias)) {
            return $entity->getLoadedRelatedAlias($alias);
        }

        return $entity->hasDirtyRelatedAlias($alias)
            ? $entity->getDirtyRelatedAlias($alias)
            : null;
    }
}
