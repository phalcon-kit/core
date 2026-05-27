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

namespace PhalconKit\Mvc\Controller\Traits\Query;

use Phalcon\Filter\Exception as FilterException;
use Phalcon\Support\Collection;
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractWith;
use PhalconKit\Support\CollectionPolicy;

trait With
{
    use AbstractWith;
    
    /**
     * Request parameter used by eager-loading REST actions.
     */
    public const string WITH_PARAMETER = 'with';

    /**
     * Controller-defined eager-load relation paths.
     *
     * The collection is used as the default relationship set for `findWith()`
     * and `findFirstWith()`. It also acts as the request allow-list when a
     * frontend sends the {@see WITH_PARAMETER} parameter to request a subset of
     * those relationships.
     */
    protected ?Collection $with = null;
    
    /**
     * Initialize the default eager-load relationship collection.
     *
     * The default is null, so `findWith()` and `findFirstWith()` load no
     * relationships unless a concrete controller sets them. Relationship
     * request parameters are intentionally closed when no default relationship
     * graph exists, because eager loading can expose extra data and create
     * expensive query plans.
     */
    public function initializeWith(): void
    {
        $this->setWith(null);
    }
    
    /**
     * Replace the default eager-load relationship collection.
     *
     * Supported collection shapes match the eager-loading loader:
     * - `['Author', 'Author.Profile']` loads those relation paths by default.
     * - `['Author' => $callable]` applies a constraint callback to that
     *   relation when the default eager-load graph is used.
     *
     * When a client sends a `with` request parameter, the same collection
     * becomes the allow-list. A client may request any configured relation path
     * or a parent path of one, so a configured `Author.Profile.Avatar` allows a
     * request for `Author.Profile` without also loading `Avatar`.
     */
    public function setWith(?Collection $with): void
    {
        $this->with = $with;
    }
    
    /**
     * Return the default eager-load relationship collection.
     *
     * A null value means the controller has no default eager-load graph and no
     * frontend-requestable relationship graph. It does not mean arbitrary
     * relationships are allowed from request parameters.
     */
    public function getWith(): ?Collection
    {
        return $this->with;
    }

    /**
     * Merge additional eager-load relation paths into the current collection.
     *
     * Merging into null creates the first default/allowed relationship graph.
     * This is useful for base controllers that define common relations and
     * resource controllers that add their own nested paths.
     */
    public function mergeWith(Collection $with): void
    {
        $this->with = CollectionPolicy::mergeNullable(
            $this->with,
            $with
        );
    }

    /**
     * Resolve the request-provided eager-load subset for `*WithAction()`.
     *
     * A null return value means the frontend did not send the `with` parameter,
     * so callers should use the controller's configured default relationship
     * graph. An empty array means the parameter was present but requested no
     * relationships. Non-empty arrays contain only relationships allowed by the
     * configured graph.
     *
     * Nested paths are first-class: a request for `Author.Profile.Avatar` is
     * passed to the eager loader as one path, and the loader builds the
     * required parent paths internally. When configured parent paths have
     * constraint callbacks, they are preserved in the returned subset.
     *
     * @return array<string|int, mixed>|null
     *
     * @throws FilterException When request parameter filtering fails.
     * @throws HttpException When the request shape is invalid or a relation is
     *     not present in the configured relationship graph.
     */
    protected function getRequestedWith(): ?array
    {
        $missing = new \stdClass();
        $requested = $this->getParam(self::WITH_PARAMETER, null, $missing);

        if ($requested === $missing) {
            return null;
        }

        return $this->filterRequestedWithRelations(
            $this->normalizeRequestedWith($requested)
        );
    }

    /**
     * Normalize the public `with` request parameter to relation paths.
     *
     * Supported request shapes:
     * - `?with=Author,Author.Profile` for comma-separated paths.
     * - `?with[]=Author&with[]=Author.Profile` for list-style paths.
     * - `?with[Author.Profile]=1` for enabled-map syntax.
     *
     * @return list<string>
     *
     * @throws HttpException When the parameter has an unsupported type.
     */
    protected function normalizeRequestedWith(mixed $requested): array
    {
        if ($requested === null || $requested === false) {
            return [];
        }

        if (is_string($requested)) {
            return $this->normalizeWithRelationList(explode(',', $requested));
        }

        if (!is_array($requested)) {
            throw new HttpException(sprintf('Invalid type for "with" parameter: expected null, bool, string, or array, got %s.', gettype($requested)), 400);
        }

        $relations = [];
        foreach ($requested as $key => $value) {
            if (is_string($key)) {
                if ($this->isWithRelationEnabledValue($value)) {
                    $relations[] = $key;
                }
                continue;
            }

            if (is_string($value)) {
                $relations = array_merge($relations, explode(',', $value));
                continue;
            }

            if ($value !== null && $value !== false) {
                throw new HttpException(sprintf('Invalid value for "with" parameter at index %s: expected relationship path string.', (string)$key), 400);
            }
        }

        return $this->normalizeWithRelationList($relations);
    }

    /**
     * Keep only requested relation paths allowed by the configured graph.
     *
     * @param list<string> $requested Relation paths requested by the frontend.
     * @return array<string|int, mixed>
     *
     * @throws HttpException When a requested relation is outside the configured
     *     relationship graph.
     */
    protected function filterRequestedWithRelations(array $requested): array
    {
        if ($requested === []) {
            return [];
        }

        $allowed = $this->getWithRelationMap();
        $filtered = [];

        foreach ($requested as $relation) {
            if (!$this->isWithRelationAllowed($relation, array_keys($allowed))) {
                throw new HttpException(sprintf('Unauthorized relationship "%s".', $relation), 403);
            }

            foreach ($allowed as $allowedRelation => $constraints) {
                if (
                    $constraints !== null
                    && $this->isSameOrParentWithRelation($allowedRelation, $relation)
                ) {
                    $filtered[$allowedRelation] = $constraints;
                }
            }

            $filtered[$relation] = $allowed[$relation] ?? null;
        }

        return $this->normalizeWithRelationMap($filtered);
    }

    /**
     * Normalize configured eager-load relationships to relation => constraint.
     *
     * The returned map intentionally follows the current eager loader contract:
     * string keys are relation paths, callable values are constraints, and list
     * values are plain relation paths.
     *
     * @return array<string, callable|null>
     */
    protected function getWithRelationMap(): array
    {
        $relations = [];

        foreach ($this->getWith()?->toArray() ?? [] as $key => $value) {
            if (is_string($key)) {
                $key = trim($key);
                if ($key !== '') {
                    $relations[$key] = is_callable($value) ? $value : null;
                }
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
                if ($value !== '') {
                    $relations[$value] = null;
                }
            }
        }

        return $relations;
    }

    /**
     * Determine whether a requested relation is present in the configured graph.
     *
     * A requested relation may be exactly configured or may be a parent of a
     * configured nested relation. The inverse is not allowed: configuring
     * `Author` does not permit a frontend to request `Author.Profile`.
     *
     * @param list<string> $allowedRelations
     */
    protected function isWithRelationAllowed(string $requested, array $allowedRelations): bool
    {
        foreach ($allowedRelations as $allowed) {
            if ($requested === $allowed || str_starts_with($allowed, $requested . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether one configured relation is the same as or parent of another.
     */
    protected function isSameOrParentWithRelation(string $candidate, string $relation): bool
    {
        return $candidate === $relation || str_starts_with($relation, $candidate . '.');
    }

    /**
     * Check whether an enabled-map `with[Relation]` value should request a path.
     */
    protected function isWithRelationEnabledValue(mixed $value): bool
    {
        return CollectionPolicy::isEnabledValue($value);
    }

    /**
     * Trim, de-duplicate, and validate relation path fragments.
     *
     * @param array<int, mixed> $relations Raw relation fragments.
     * @return list<string>
     *
     * @throws HttpException When a relation path is not scalar.
     */
    protected function normalizeWithRelationList(array $relations): array
    {
        $normalized = [];

        foreach ($relations as $relation) {
            if (!is_scalar($relation)) {
                throw new HttpException('Invalid relationship path in "with" parameter: expected scalar path.', 400);
            }

            $relation = trim((string)$relation);
            if ($relation !== '') {
                $normalized[] = $relation;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Convert a relation map back to the eager loader's compact argument shape.
     *
     * Relations without constraints are returned as list values so the payload
     * remains equivalent to the existing `['Author', 'Author.Profile']` style.
     * Constrained relations keep their string key so the loader receives the
     * callback on the exact configured path.
     *
     * @param array<string, callable|null> $relations
     * @return array<string|int, mixed>
     */
    protected function normalizeWithRelationMap(array $relations): array
    {
        $normalized = [];

        foreach ($relations as $relation => $constraints) {
            if ($constraints !== null) {
                $normalized[$relation] = $constraints;
                continue;
            }

            $normalized[] = $relation;
        }

        return $normalized;
    }
}
