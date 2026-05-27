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

namespace PhalconKit\Support\Options;

/**
 * Minimal mutable key/value option manager contract.
 *
 * This interface is useful when consumers need a small runtime options store
 * without exposing the full `OptionsInterface` lifecycle. Values are keyed by
 * strings and may be any PHP value.
 */
interface ManagerInterface
{
    /**
     * Return an option value or the provided default when it is not set.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Return true when an option is present and not null.
     */
    public function has(string $key): bool;

    /**
     * Store or replace an option value.
     */
    public function set(string $key, mixed $value = null): void;

    /**
     * Remove one option value when it exists.
     */
    public function remove(string $key): void;

    /**
     * Restore the manager to its constructor/default option set.
     */
    public function reset(): void;

    /**
     * Remove all current option values.
     */
    public function clear(): void;
}
