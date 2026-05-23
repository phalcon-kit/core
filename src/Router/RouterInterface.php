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

namespace PhalconKit\Router;

use Phalcon\Di\InjectionAwareInterface;

/**
 * Shared contract for PhalconKit routers.
 *
 * Routers expose their configured state as arrays for diagnostics, CLI output,
 * and tests while still supporting native Phalcon DI injection. MVC, CLI, and
 * WebSocket routers can implement this interface so bootstrap code can resolve
 * them through the same typed DI contract.
 */
interface RouterInterface extends InjectionAwareInterface
{
    /**
     * Export router configuration/state as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Set default routing values.
     *
     * Native Phalcon router implementations return different concrete types
     * from `setDefaults()`, so this interface deliberately mirrors the native
     * loose return surface.
     *
     * @param array<string, mixed> $defaults
     */
    /** @psalm-suppress MissingReturnType */
    public function setDefaults(array $defaults);
}
