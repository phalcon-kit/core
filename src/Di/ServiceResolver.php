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
     * Require a native Phalcon container to expose PhalconKit typed helpers.
     *
     * Use this helper at framework boundaries that receive Phalcon's native
     * DI contract but still need PhalconKit's stricter service APIs. The
     * returned value is narrowed to `PhalconKit\Di\DiInterface`, so callers can
     * immediately use `getTyped()` and `getConfig()` without repeating
     * instanceof checks or leaking native Phalcon errors.
     *
     * @param NativeDiInterface $di Container to validate.
     * @param string $operationDescription Human-readable operation used in the
     *     exception message, such as `create MVC application` or
     *     `resolve DI service "view" for provider setup`.
     * @param string $containerDescription Human-readable container label used
     *     in the exception message, such as `the application DI`.
     * @throws ServiceException When the container does not implement
     *     PhalconKit's DI contract.
     */
    public static function requirePhalconKitContainer(
        NativeDiInterface $di,
        string $operationDescription,
        string $containerDescription = 'the provided DI'
    ): DiInterface {
        $operationDescription = trim($operationDescription);
        if ($operationDescription === '') {
            $operationDescription = 'use the DI container';
        }

        if (!$di instanceof DiInterface) {
            throw new ServiceException(sprintf(
                'Could not %s because %s must implement "%s"; got "%s".',
                $operationDescription,
                $containerDescription,
                DiInterface::class,
                get_debug_type($di)
            ));
        }

        return $di;
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
     * Resolve a typed service from a container or create a typed default.
     *
     * Use this when a framework component supports optional DI replacement but
     * also has a local default implementation. The container is still required
     * to be a PhalconKit DI because a caller-provided container participates in
     * the framework boundary even when the specific service is absent.
     *
     * @template T of object
     * @param NativeDiInterface $di Container to resolve from.
     * @param string $name DI service name to resolve when registered.
     * @param class-string<T> $expectedType Required runtime service contract.
     * @param callable():object $defaultFactory Factory used when the service is
     *     not registered in the container.
     * @param mixed $parameters Optional parameters forwarded to `getTyped()`.
     * @param string|null $context Human-readable caller context for exception
     *     messages, such as `MVC module services`.
     * @return T
     * @throws ServiceException When the container is not a PhalconKit DI, the
     *     registered service or default factory output does not match the
     *     expected type, or service resolution fails.
     */
    public static function fromContainerOrDefault(
        NativeDiInterface $di,
        string $name,
        string $expectedType,
        callable $defaultFactory,
        mixed $parameters = null,
        ?string $context = null
    ): object {
        $di = self::requirePhalconKitContainer(
            $di,
            sprintf('resolve DI service "%s"%s', $name, self::contextSuffix($context))
        );
        if ($di->has($name)) {
            return $di->getTyped($name, $expectedType, $parameters);
        }

        $service = $defaultFactory();
        if (!$service instanceof $expectedType) {
            throw new ServiceException(sprintf(
                'Expected default DI service "%s"%s to be an instance of "%s"; got "%s".',
                $name,
                self::contextSuffix($context),
                $expectedType,
                get_debug_type($service)
            ));
        }

        return $service;
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
        $di = self::requirePhalconKitContainer(
            $di,
            sprintf('resolve DI service "%s"%s', $name, self::contextSuffix($context)),
            $containerDescription
        );

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
