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

namespace PhalconKit\Di;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use PhalconKit\Mvc\Model\Behavior\Blameable;
use PhalconKit\Mvc\Model\Behavior\Security;

/**
 * Clears request-scoped framework state before a new logical request.
 *
 * `Bootstrap::run()` and the base WebSocket task call this before every
 * request or callback. It resets:
 *
 * - every already-instantiated shared service implementing
 *   `RequestScopedInterface` (for example the identity manager's cached
 *   claim, user, and impersonating user);
 * - the model security behavior's cached ACL roles;
 * - the Blameable behavior's parent-audit link.
 *
 * Instances are found in two places: shared services the container has
 * built, and any service (shared or not) cached by `Di::getShared()`, which
 * is also how `Injectable` magic properties such as `$this->identity` resolve
 * services. Each instance is reset once. Services that were never
 * instantiated are left untouched, so the reset does not build services a
 * request does not use. The cached ACL permission matrix
 * (`Security::setAcl()`) is configuration, not identity, and is kept.
 */
final class RequestState
{
    private static ?\ReflectionProperty $sharedInstances = null;

    /**
     * Reset request-scoped state held by shared services and model behaviors.
     *
     * @param DiInterface $di Container whose shared services are inspected.
     */
    public static function reset(DiInterface $di): void
    {
        $reset = [];
        foreach (self::getBuiltInstances($di) as $instance) {
            if (!$instance instanceof RequestScopedInterface) {
                continue;
            }

            $id = spl_object_id($instance);
            if (!isset($reset[$id])) {
                $reset[$id] = true;
                $instance->resetRequestState();
            }
        }

        Security::setRoles(null);
        Blameable::resetParentId();
    }

    /**
     * Yield the service instances the container has already built.
     *
     * Covers shared services resolved through `get()` or registered as
     * instances, plus every instance cached by `Di::getShared()`, including
     * non-shared services fetched that way. The latter cache has no public
     * accessor, so it is read through reflection on `Phalcon\Di\Di`; other
     * `DiInterface` implementations only contribute their shared services.
     *
     * @return \Generator<int, mixed>
     */
    private static function getBuiltInstances(DiInterface $di): \Generator
    {
        foreach ($di->getServices() as $service) {
            if (!$service->isShared()) {
                continue;
            }

            $definition = $service->getDefinition();
            $built = $service->isResolved()
                || (is_object($definition) && !$definition instanceof \Closure);
            if ($built) {
                yield $service->resolve(null, $di);
            }
        }

        if ($di instanceof Di) {
            self::$sharedInstances ??= new \ReflectionProperty(Di::class, 'sharedInstances');
            $instances = self::$sharedInstances->getValue($di);
            if (is_array($instances)) {
                yield from array_values($instances);
            }
        }
    }
}
