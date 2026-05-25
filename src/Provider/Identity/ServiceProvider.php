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

namespace PhalconKit\Provider\Identity;

use PhalconKit\Di\DiInterface;
use PhalconKit\Identity\Manager as Identity;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the identity manager service.
 *
 * The identity manager owns authentication state, session/JWT fallback behavior,
 * impersonation helpers, role checks, and identity model lookups. Options are
 * read from `identity` config unless runtime options are passed during service
 * resolution.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'identity';
    
    /**
     * Register the shared `identity` service.
     *
     * Runtime options are useful for tests and specialized bootstraps. Normal
     * applications should prefer config so authentication behavior is stable
     * across controllers, tasks, and services.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?array $options = null) use ($di) {
            
            $config = $di->getConfig();
            
            $options ??= $config->pathToArray('identity');
            
            $identity = new Identity($options);
            $identity->setDI($di);
            
            return $identity;
        });
    }
}
