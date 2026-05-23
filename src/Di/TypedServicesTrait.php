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
use PhalconKit\Exception\ServiceException;

/**
 * Implements typed service lookups for PhalconKit DI containers.
 *
 * Classes using this trait must provide Phalcon's native `get()` method, which
 * is why the trait is only intended for DI container classes that extend native
 * Phalcon containers.
 */
trait TypedServicesTrait
{
    /**
     * Resolve a DI service and enforce its runtime type.
     *
     * Missing services and wrong service types are wrapped in ServiceException
     * so framework callers get stable PhalconKit failures instead of raw
     * Phalcon exceptions, PHP type errors, or disabled assertion behavior.
     *
     * @template T of object
     * @param string $name DI service name to resolve.
     * @param class-string<T> $expectedType
     * @param mixed $parameters Optional parameters forwarded to the underlying
     *        Phalcon DI `get()` call.
     * @return T
     * @throws ServiceException When the service cannot be resolved or does not
     *         implement the expected type.
     */
    public function getTyped(string $name, string $expectedType, mixed $parameters = null): object
    {
        try {
            $service = $this->get($name, $parameters);
        }
        catch (\Throwable $e) {
            throw new ServiceException(sprintf(
                'Could not resolve DI service "%s".',
                $name
            ), previous: $e);
        }

        if (!$service instanceof $expectedType) {
            throw new ServiceException(sprintf(
                'Expected DI service "%s" to be an instance of "%s"; got "%s".',
                $name,
                $expectedType,
                get_debug_type($service)
            ));
        }

        return $service;
    }

    /**
     * Resolve a config service as a PhalconKit ConfigInterface.
     *
     * This is the preferred path for providers and bootstraps that need config.
     * It delegates to getTyped() so missing or invalid config services fail with
     * the same explicit ServiceException behavior as other typed lookups.
     *
     * @param string $name DI service name containing the config object.
     * @throws ServiceException When the service cannot be resolved or is not a
     *         ConfigInterface instance.
     */
    public function getConfig(string $name = 'config'): ConfigInterface
    {
        return $this->getTyped($name, ConfigInterface::class);
    }
}
