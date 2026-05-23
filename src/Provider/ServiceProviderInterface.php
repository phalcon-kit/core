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
 * Interface ServiceProviderInterface
 */
interface ServiceProviderInterface extends \Phalcon\Di\InjectionAwareInterface
{
    /**
     * Register application service.
     */
    public function register(DiInterface $di): void;
    
    /**
     * Package boot method.
     */
    public function boot(): void;
    
    /**
     * Configures the current service provider.
     */
    public function configure(): void;
    
    /**
     * Get the Service name.
     */
    public function getName(): string;
}
