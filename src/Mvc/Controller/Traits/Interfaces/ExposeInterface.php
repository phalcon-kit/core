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
 * Contract for controller payload exposure helpers.
 *
 * Exposure rules are passed to the shared exposer so REST actions can return
 * stable public arrays without leaking protected or internal fields.
 */
interface ExposeInterface
{
    /**
     * Expose one item according to an optional rule map.
     *
     * @param mixed $item Item to expose.
     * @param array<string|int, mixed>|null $expose Exposure rule definition.
     *
     * @return array<string, mixed>
     */
    public function expose(mixed $item, ?array $expose = null): array;
    
    /**
     * Expose each item in a list response.
     *
     * @param iterable<array-key, mixed> $items Items to expose.
     * @param array<string|int, mixed>|null $expose List exposure rules.
     *
     * @return array<int|string, mixed>
     */
    public function listExpose(iterable $items, ?array $expose = null): array;
    
    /**
     * Expose each item in an export response.
     *
     * @param iterable<array-key, mixed> $items Items to expose.
     * @param array<string|int, mixed>|null $expose Export exposure rules.
     *
     * @return array<int|string, mixed>
     */
    public function exportExpose(iterable $items, ?array $expose = null): array;
}
