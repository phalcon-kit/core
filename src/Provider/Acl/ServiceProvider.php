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

namespace PhalconKit\Provider\Acl;

use PhalconKit\Di\DiInterface;
use PhalconKit\Acl\Acl;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the access-control service.
 *
 * ACL options come from `acl` config and permission definitions come from
 * `permissions` config. The provider merges both into `PhalconKit\Acl\Acl` so
 * dispatcher security checks, controllers, and application services share one
 * permission graph.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'acl';
    
    /**
     * Register the shared `acl` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->getConfig();
            $aclConfig = $config->pathToArray('acl') ?? [];
            $permissionsConfig = $config->pathToArray('permissions') ?? [];
            $options = array_merge($aclConfig, ['permissions' => $permissionsConfig]);
            
            return new Acl($options);
        });
    }
}
