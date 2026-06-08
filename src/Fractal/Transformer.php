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
 * Base transformer for Fractal resources backed by Phalcon models.
 *
 * The transformer is DI-aware so concrete API transformers can resolve shared
 * services without introducing their own container plumbing. It also provides
 * helpers for exposing relationships only when they were already loaded by the
 * model layer, which avoids accidental lazy-loading and keeps response costs
 * predictable for REST endpoints.
 *
 * Concrete transformers should call `includeCollectionIfLoaded()` and
 * `includeItemIfLoaded()` from Fractal include methods when an include should
 * reflect the model's loaded relationship state instead of forcing a query.
 *
 * This convention keeps include behavior aligned with controller eager-loading:
 * the controller decides what relationships are loaded, and the transformer
 * serializes only that already-known state.
 *
 * @see https://fractal.thephpleague.com/transformers/
 * @see https://docs.phalcon.io/5.14/db-models-relationships/
 */
class Transformer extends TransformerAbstract implements InjectionAwareInterface
{
    use InjectableTrait;

    /**
     * Build a Fractal collection resource for a loaded relationship alias.
     *
     * If the alias is not available, or if the loaded value is not iterable, an
     * empty collection is returned. This keeps collection includes stable for
     * clients while still avoiding implicit database reads.
     *
     * Returning an empty collection for missing/non-iterable values is
     * deliberate: this helper is for "many" relationships, and an absent loaded
     * relation should serialize as an empty include rather than trigger another
     * model query from inside a transformer.
     *
     * @param ModelInterface $entity Model that may expose loaded relationship
     *     aliases through PhalconKit relationship helpers.
     * @param string $alias Relationship alias requested by the transformer.
     * @param Transformer $transformer Transformer used for each related item.
     *
     * @return Collection Fractal collection resource for the loaded relation.
     */
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

    /**
     * Build a Fractal item resource for a loaded relationship alias.
     *
     * Missing aliases, null values, and iterable values return null because
     * Fractal item includes are meant for one related model. Use
     * `includeCollectionIfLoaded()` when the relation may contain many records.
     *
     * Returning null tells Fractal to omit the include instead of inventing a
     * placeholder object. This avoids confusing one-to-one response shapes when
     * the requested relation was not loaded by the controller/query layer.
     *
     * @param ModelInterface $entity Model that may expose loaded relationship
     *     aliases through PhalconKit relationship helpers.
     * @param string $alias Relationship alias requested by the transformer.
     * @param Transformer $transformer Transformer used for the related model.
     *
     * @return Item|null Fractal item resource when a single related model is
     *     available, or null when the include should be omitted.
     */
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

    /**
     * Determine whether a relationship alias was already populated on a model.
     *
     * PhalconKit tracks both loaded aliases and dirty aliases. Both are treated
     * as explicitly available values because they represent state already known
     * to the model rather than a relation that must be queried.
     *
     * @param ModelInterface $entity Model being inspected.
     * @param string $alias Relationship alias as used by the transformer.
     *
     * @return bool True when the alias has eager-loaded or dirty in-memory data.
     */
    protected function isRelationAliasLoaded(ModelInterface $entity, string $alias): bool
    {
        return $entity instanceof RelationshipInterface
            && ($entity->hasDirtyRelatedAlias($alias) || $entity->hasLoadedRelatedAlias($alias));
    }

    /**
     * Return the loaded or dirty value for a relationship alias.
     *
     * Loaded aliases take priority over dirty aliases so eager-loaded data wins
     * when both stores contain a value. Null is returned for models that do not
     * implement PhalconKit's relationship contract or for aliases that have not
     * been populated.
     *
     * @param ModelInterface $entity Model being inspected.
     * @param string $alias Relationship alias as used by the transformer.
     *
     * @return mixed Relationship value, commonly a model, iterable resultset, or
     *     null when no explicit relation value exists.
     */
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
