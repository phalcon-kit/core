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

namespace PhalconKit\Provider;

use PhalconKit\Di\DiInterface;

/**
 * Contract for services that register PhalconKit runtime dependencies.
 *
 * Providers are the boundary between configuration and concrete services in
 * the DI container. They receive a PhalconKit DI implementation, not a native
 * Phalcon-only container, so implementations can use `getConfig()`,
 * `getTyped()`, and other framework-level container guarantees while wiring
 * services.
 */
interface ServiceProviderInterface extends \Phalcon\Di\InjectionAwareInterface
{
    /**
     * Registers one or more services in the DI container.
     *
     * Implementations normally register the main service under `getName()` and
     * should preserve the configured service contract when replacing a core
     * provider. Invalid configuration should fail with explicit exceptions so
     * application startup reports the real problem.
     */
    public function register(DiInterface $di): void;
    
    /**
     * Optional post-registration hook for provider-owned initialization.
     *
     * The default `Bootstrap` currently registers providers directly and does
     * not iterate provider instances after registration. Applications or custom
     * bootstraps that need this hook may call it explicitly.
     */
    public function boot(): void;
    
    /**
     * Configures the provider before services are registered.
     *
     * `AbstractServiceProvider` calls this method from its constructor after
     * storing the DI container. Use it for provider-local setup that should run
     * before `register()`, not for creating DI services.
     */
    public function configure(): void;
    
    /**
     * Returns the stable DI service name managed by this provider.
     *
     * Downstream injectables and config overrides rely on this name, so provider
     * replacements should keep the same name unless they intentionally introduce
     * a new service.
     */
    public function getName(): string;
}
