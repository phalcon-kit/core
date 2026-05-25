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
 * Registers the Facebook OAuth2 provider.
 *
 * Options are read from `oauth2.facebook`. The provider resolves the request
 * service to convert a configured relative `redirectUri` into an absolute URI
 * using the current scheme, host, and non-default port. It also resolves the
 * session service so OAuth state flows have an active session-backed context.
 *
 * @link https://github.com/tegaphilip/padlock
 * @link https://oauth2.thephpleague.com/framework-integrations/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'oauth2Facebook';
    
    /**
     * Register the shared `oauth2Facebook` service.
     *
     * @throws \PhalconKit\Exception\ServiceException When required `session` or
     *     `request` services are unavailable or have unexpected types.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            $config = $di->getConfig();
    
            $session = $di->getTyped('session', Manager::class);
    
            $request = $di->getTyped('request', Request::class);
            
            $oauthConfig = $config->pathToArray('oauth2') ?? [];
            $oauthFacebookConfig = $oauthConfig['facebook'] ?? [];
            
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
