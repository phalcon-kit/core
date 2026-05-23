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

use Phalcon\Di\Di as PhalconDi;
use Phalcon\Di\DiInterface as NativeDiInterface;
use PhalconKit\Exception\ServiceException;

/**
 * Shared typed service resolver for static helpers and native Phalcon bridges.
 *
 * Normal PhalconKit code should prefer calling `$di->getTyped()` or
 * `$di->getConfig()` directly. This resolver exists for places that do not own
 * a typed `DiInterface` property yet, such as static helper APIs, native
 * Phalcon extension points, or compatibility code that receives a native
 * `Phalcon\Di\DiInterface` and needs to enforce the PhalconKit container
 * boundary before resolving a service.
 */
final class ServiceResolver
{
    private function __construct()
    {
    }

    /**
     * Resolve a typed service from an explicit DI container.
     *
     * Use this method when the caller has obtained a DI container from a native
     * Phalcon API and needs to verify that it is actually a PhalconKit
     * container before using typed lookups. Missing services fail before
     * resolution so the optional context can identify the public API that needed
     * the service.
     *
     * @template T of object
     * @param NativeDiInterface $di Container to resolve from.
     * @param string $name DI service name to resolve.
     * @param class-string<T> $expectedType Required runtime service contract.
     * @param mixed $parameters Optional parameters forwarded to `getTyped()`.
     * @param string|null $context Human-readable caller context for exception
     *     messages, such as `PhalconKit tag helpers`.
     * @return T
     * @throws ServiceException When the container is not a PhalconKit DI, the
     *     service is missing, or the service does not match the expected type.
     */
    public static function fromContainer(
        NativeDiInterface $di,
        string $name,
        string $expectedType,
        mixed $parameters = null,
        ?string $context = null
    ): object {
        return self::fromPhalconKitContainer(
            $di,
            $name,
            $expectedType,
            $parameters,
            $context,
            'the provided DI'
        );
    }

    /**
     * Resolve a typed service from Phalcon's default DI container.
     *
     * Use this method only for static helpers or native Phalcon APIs that
     * already depend on the default container. Framework and application code
     * that already has a `DiInterface` should call `fromContainer()` or
     * `$di->getTyped()` instead.
     *
     * @template T of object
     * @param string $name DI service name to resolve.
     * @param class-string<T> $expectedType Required runtime service contract.
     * @param mixed $parameters Optional parameters forwarded to `getTyped()`.
     * @param string|null $context Human-readable caller context for exception
     *     messages, such as `PhalconKit tag helpers`.
     * @return T
     * @throws ServiceException When no default DI exists, the default container
     *     is not a PhalconKit DI, the service is missing, or the service does
     *     not match the expected type.
     */
    public static function fromDefault(
        string $name,
        string $expectedType,
        mixed $parameters = null,
        ?string $context = null
    ): object {
        $di = PhalconDi::getDefault();
        if (!$di instanceof NativeDiInterface) {
            throw new ServiceException(sprintf(
                'Could not resolve DI service "%s"%s because no default DI is available.',
                $name,
                self::contextSuffix($context)
            ));
        }

        return self::fromPhalconKitContainer(
            $di,
            $name,
            $expectedType,
            $parameters,
            $context,
            'the default DI'
        );
    }

    /**
     * Resolve from a container after enforcing the PhalconKit DI boundary.
     *
     * @template T of object
     * @param NativeDiInterface $di Container to inspect.
     * @param class-string<T> $expectedType Required runtime service contract.
     * @return T
     */
    private static function fromPhalconKitContainer(
        NativeDiInterface $di,
        string $name,
        string $expectedType,
        mixed $parameters,
        ?string $context,
        string $containerDescription
    ): object {
        if (!$di instanceof DiInterface) {
            throw new ServiceException(sprintf(
                'Could not resolve DI service "%s"%s because %s must implement "%s"; got "%s".',
                $name,
                self::contextSuffix($context),
                $containerDescription,
                DiInterface::class,
                get_debug_type($di)
            ));
        }

        if (!$di->has($name)) {
            throw new ServiceException(sprintf(
                'Could not resolve DI service "%s"%s.',
                $name,
                self::contextSuffix($context)
            ));
        }

        return $di->getTyped($name, $expectedType, $parameters);
    }

    /**
     * Formats optional caller context for human-readable exception messages.
     */
    private static function contextSuffix(?string $context): string
    {
        return $context !== null && $context !== '' ? ' for ' . $context : '';
    }
}
