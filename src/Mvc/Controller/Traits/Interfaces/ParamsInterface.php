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

interface ParamsInterface
{
    public function getParam(string $key, array|string|null $filters = null, mixed $default = null, ?array $params = null): mixed;
    
    public function hasParam(string $key, ?array $params = null, bool $cached = true): bool;
    
    /**
     * @return array<array-key, mixed>
     */
    public function getParams(?array $fields = null, bool $cached = true, bool $deep = true): array;
    
    public function getAllParams(?array $filters = null, bool $cached = true, bool $deep = true): array;
    
    /**
     * @param array<string, mixed> $params
     * @param array<string, array|string> $filters
     * @return array<string, mixed>
     */
    public function applyFilters(array $params, array $filters, bool $deep = true): array;
    
    /**
     * @param array<string, array|string> $filters
     */
    public function setDefaultFilters(array $filters): static;
    
    /**
     * @param array<string, array|string> $filters
     */
    public function addDefaultFilters(array $filters): static;
    
    /**
     * @param string|array<int, string> $keys
     */
    public function removeFilters(string|array $keys): static;

    public function clearDefaultFilters(): static;

    public function getDefaultFilters(): array;

    public function getRawParams(bool $cached = true): array;
}
