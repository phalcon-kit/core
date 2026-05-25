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

namespace PhalconKit\Provider\Oauth2Google;

use League\OAuth2\Client\Provider\Google;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the Google OAuth2 provider.
 *
 * Options are read from `oauth2.google` and passed directly to League OAuth2's
 * Google provider. Redirect URI handling is intentionally left to config for
 * this provider, unlike the Facebook provider's request-relative helper.
 *
 * @link https://github.com/tegaphilip/padlock
 * @link https://oauth2.thephpleague.com/framework-integrations/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'oauth2Google';
    
    /**
     * Register the shared `oauth2Google` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            $config = $di->getConfig();

            $oauthConfig = $config->pathToArray('oauth2') ?? [];
            $oauthGoogleConfig = $oauthConfig['google'] ?? [];
            
            return new Google($oauthGoogleConfig);
        });
    }
}
