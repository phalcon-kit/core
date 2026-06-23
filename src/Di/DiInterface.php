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

use PhalconKit\Config\ConfigInterface;

/**
 * PhalconKit dependency injection contract.
 *
 * This interface extends Phalcon's native DI contract with typed lookup helpers
 * used by the PhalconKit bootstrap, service providers, and downstream
 * applications. Code that participates in the PhalconKit provider/bootstrap
 * boundary should type against this interface instead of native
 * `Phalcon\Di\DiInterface` so `getTyped()` and `getConfig()` are available.
 *
 * @see https://docs.phalcon.io/5.16/di/
 */
interface DiInterface extends \Phalcon\Di\DiInterface
{
    /**
     * Resolve a DI service and enforce its runtime type.
     *
     * Use this helper when the caller knows the expected service contract. It
     * keeps provider and framework code concise while still failing with a
     * clear framework exception when a service is missing or misconfigured.
     *
     * @template T of object
     * @param string $name DI service name to resolve.
     * @param class-string<T> $expectedType
     * @param mixed $parameters Optional parameters forwarded to the underlying
     *        Phalcon DI `get()` call.
     * @return T
     * @throws \PhalconKit\Exception\ServiceException When the service cannot be
     *         resolved or does not implement the expected type.
     */
    public function getTyped(string $name, string $expectedType, mixed $parameters = null): object;

    /**
     * Resolve a config service and enforce the PhalconKit config contract.
     *
     * The default service name is `config`, but tests and specialized
     * bootstraps may pass a different service name when they intentionally
     * register more than one config object.
     *
     * @param string $name DI service name containing the config object.
     * @throws \PhalconKit\Exception\ServiceException When the service cannot be
     *         resolved or is not a ConfigInterface instance.
     */
    public function getConfig(string $name = 'config'): ConfigInterface;
}
