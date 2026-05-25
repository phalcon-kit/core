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

namespace PhalconKit\Provider\Oauth2Client;

use League\OAuth2\Client\Provider\GenericProvider;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers a generic OAuth2 client provider.
 *
 * Client options are read from `oauth2.client` and passed to League OAuth2's
 * `GenericProvider`. Use this provider for OAuth2 services that are not covered
 * by a dedicated provider such as Google or Facebook.
 *
 * @link https://github.com/tegaphilip/padlock
 * @link https://oauth2.thephpleague.com/framework-integrations/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'oauth2Client';
    
    /**
     * Register the shared `oauth2Client` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            $config = $di->getConfig();
            $oauthConfig = $config->pathToArray('oauth2') ?? [];
            
            return new GenericProvider($oauthConfig['client'] ?? []);
        });
    }
}
