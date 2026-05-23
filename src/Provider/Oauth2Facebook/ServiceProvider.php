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

namespace PhalconKit\Provider\Oauth2Facebook;

use League\OAuth2\Client\Provider\Facebook;
use PhalconKit\Di\DiInterface;
use Phalcon\Session\Manager;
use PhalconKit\Http\Request;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * @link https://github.com/tegaphilip/padlock
 * @link https://oauth2.thephpleague.com/framework-integrations/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'oauth2Facebook';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            $config = $di->getConfig();
    
            $session = $di->getTyped('session', Manager::class);
    
            $request = $di->getTyped('request', Request::class);
            
            $oauthConfig = $config->pathToArray('oauth2') ?? [];
            $oauthFacebookConfig = $oauthConfig['facebook'] ?? [];
            
            // Set the full url
            $secure = $request->isSecure();
            $scheme = $request->getScheme() . '://';
            $host = $request->getHttpHost();
            $port = $request->getPort();
            $defaultPort = $secure ? 443 : 80;
            $port = $port !== $defaultPort ? ':' . $port : null;
            $redirectUri = $oauthFacebookConfig['redirectUri'] ?? '';
            $oauthFacebookConfig['redirectUri'] = $scheme . $host . $port . ($redirectUri ?: '');
            
            return new Facebook($oauthFacebookConfig);
        });
    }
}
