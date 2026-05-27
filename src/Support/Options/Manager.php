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
 * Default in-memory option manager.
 *
 * The manager is a thin adapter around the `Options` trait. It exists for code
 * that wants a concrete service/object implementing both the full option
 * lifecycle and the shorter `get/set/remove/reset/clear` manager contract.
 */
class Manager implements ManagerInterface, OptionsInterface
{
    use Options;

    /**
     * Return an option value or the provided default when it is not set.
     */
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getOption($key, $default);
    }

    /**
     * Return true when an option is present and not null.
     */
    public function has(string $key): bool
    {
        return $this->hasOption($key);
    }

    /**
     * Store or replace an option value.
     */
    #[\Override]
    public function set(string $key, mixed $value = null): void
    {
        $this->setOption($key, $value);
    }

    /**
     * Remove one option value when it exists.
     */
    #[\Override]
    public function remove(string $key): void
    {
        $this->removeOption($key);
    }

    /**
     * Restore the manager to its default option set.
     */
    #[\Override]
    public function reset(): void
    {
        $this->resetOptions();
    }

    /**
     * Remove all current option values.
     */
    #[\Override]
    public function clear(): void
    {
        $this->clearOptions();
    }
}
