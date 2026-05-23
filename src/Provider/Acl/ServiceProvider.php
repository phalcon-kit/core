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

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'acl';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            // config
            $config = $di->getConfig();
            
            // acl config
            $aclConfig = $config->pathToArray('acl') ?? [];
            
            // permissions config
            $permissionsConfig = $config->pathToArray('permissions') ?? [];
            
            // acl options
            $options = array_merge($aclConfig, ['permissions' => $permissionsConfig]);
            
            return new Acl($options);
        });
    }
}
