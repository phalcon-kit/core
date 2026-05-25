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

namespace PhalconKit\Mvc\Controller\Traits\Interfaces;

/**
 * Contract for filtered REST request parameter access.
 *
 * Implementations merge route, query, body, and raw JSON parameters according
 * to controller policy, then apply Phalcon filter services on demand.
 */
interface ParamsInterface
{
    /**
     * Return one filtered parameter value.
     *
     * @param string $key Parameter key.
     * @param array|string|null $filters Filter name(s) to apply.
     * @param mixed $default Default value when the key is missing.
     * @param array<string, mixed>|null $params Optional parameter source.
     */
    public function getParam(string $key, array|string|null $filters = null, mixed $default = null, ?array $params = null): mixed;
    
    /**
     * Determine whether a parameter exists.
     *
     * @param array<string, mixed>|null $params Optional parameter source.
     * @param bool $cached Whether cached controller parameters may be reused.
     */
    public function hasParam(string $key, ?array $params = null, bool $cached = true): bool;
    
    /**
     * Return selected filtered controller parameters.
     *
     * @param list<string>|array<string, array|string>|null $fields Optional
     *     field names or field-to-filter map.
     * @param bool $cached Whether cached controller parameters may be reused.
     * @param bool $deep Whether nested parameters should be filtered
     *     recursively.
     *
     * @return array<array-key, mixed>
     */
    public function getParams(?array $fields = null, bool $cached = true, bool $deep = true): array;
    
    /**
     * Return all request parameters after default filters are applied.
     *
     * @param array<string, array|string>|null $filters Optional filter map.
     * @param bool $cached Whether cached controller parameters may be reused.
     * @param bool $deep Whether nested parameters should be filtered
     *     recursively.
     *
     * @return array<array-key, mixed>
     */
    public function getAllParams(?array $filters = null, bool $cached = true, bool $deep = true): array;
    
    /**
     * @param array<string, mixed> $params
     * @param array<string, array|string> $filters
     * @param bool $deep Whether nested parameters should be filtered
     *     recursively.
     *
     * @return array<string, mixed>
     */
    public function applyFilters(array $params, array $filters, bool $deep = true): array;
    
    /**
     * Replace default filters applied by `getAllParams()`.
     *
     * @param array<string, array|string> $filters
     */
    public function setDefaultFilters(array $filters): static;
    
    /**
     * Merge additional default filters.
     *
     * @param array<string, array|string> $filters
     */
    public function addDefaultFilters(array $filters): static;
    
    /**
     * Remove one or more default filters by parameter key.
     *
     * @param string|array<int, string> $keys
     */
    public function removeFilters(string|array $keys): static;

    /**
     * Remove all default filters.
     */
    public function clearDefaultFilters(): static;

    /**
     * Return default filters applied by `getAllParams()`.
     *
     * @return array<string, array|string>
     */
    public function getDefaultFilters(): array;

    /**
     * Return unfiltered request parameters.
     *
     * @param bool $cached Whether cached raw parameters may be reused.
     *
     * @return array<array-key, mixed>
     */
    public function getRawParams(bool $cached = true): array;
}
