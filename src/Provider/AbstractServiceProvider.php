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
use PhalconKit\Di\Injectable;
use PhalconKit\Exception\LogicException;

/**
 * Base implementation for PhalconKit DI service providers.
 *
 * Concrete providers define a stable `$serviceName` and implement
 * `register(DiInterface $di)` to bind their service into the container. The
 * constructor stores the PhalconKit DI container and runs `configure()` so
 * subclasses can prepare provider-local options before registration.
 */
abstract class AbstractServiceProvider extends Injectable implements ServiceProviderInterface
{
    /**
     * Stable DI service name managed by this provider.
     *
     * This value is part of the provider contract because controllers, tasks,
     * other injectables, and replacement providers resolve services by name.
     * Concrete providers must set it to a non-empty value.
     */
    protected string $serviceName;
    
    /**
     * Stores the DI container and prepares the provider for registration.
     *
     * The constructor intentionally requires `PhalconKit\Di\DiInterface` so
     * providers can rely on typed service helpers during configuration and
     * registration.
     *
     * @throws LogicException When a concrete provider does not define a
     *     non-empty service name.
     */
    public function __construct(DiInterface $di)
    {
        if (empty($this->serviceName)) {
            throw new LogicException(sprintf('The service provider defined in "%s" cannot have an empty name.', get_class($this)));
        }
        $this->setDI($di);
        $this->configure();
    }
    
    /**
     * Returns the DI service name managed by this provider.
     */
    #[\Override]
    public function getName(): string
    {
        return $this->serviceName;
    }
    
    /**
     * Optional post-registration hook.
     *
     * The base implementation is intentionally empty. Custom bootstraps or
     * application code may call this method for provider-specific startup work
     * after all services have been registered.
     */
    #[\Override]
    public function boot(): void
    {
    }
    
    /**
     * Optional provider-local configuration hook.
     *
     * This runs during construction after DI has been stored and before
     * `register()` is called. Use it to normalize provider options or prepare
     * lightweight state; service creation belongs in `register()`.
     */
    #[\Override]
    public function configure(): void
    {
    }
}
