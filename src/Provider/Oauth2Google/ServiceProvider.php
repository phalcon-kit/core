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
use Phalcon\Di\DiInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * @link https://github.com/tegaphilip/padlock
 * @link https://oauth2.thephpleague.com/framework-integrations/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'oauth2Google';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            $config = $di->get('config');
            assert($config instanceof ConfigInterface);

            $oauthConfig = $config->pathToArray('oauth2') ?? [];
            $oauthGoogleConfig = $oauthConfig['google'] ?? [];
            
            return new Google($oauthGoogleConfig);
        });
    }
}
