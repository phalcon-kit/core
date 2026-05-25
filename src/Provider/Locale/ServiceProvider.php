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

namespace PhalconKit\Provider\Locale;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Locale;

/**
 * Registers the locale resolver service.
 *
 * Locale options are read from `locale` config and control the default locale,
 * session key, resolution mode, and allowed locale list. The service is shared
 * because locale state can be reused by translation, view, and controller
 * logic during the same request.
 */
class ServiceProvider extends AbstractServiceProvider
{
    /**
     * Default locale options used when config does not provide values.
     *
     * @var array{default: string, sessionKey: string, mode: string, allowed: array<int, string>}
     */
    public array $defaultOptions = [
        'default' => 'en',
        'sessionKey' => 'phalcon-kit-locale',
        'mode' => Locale::MODE_DEFAULT,
        'allowed' => ['en'],
    ];
    
    protected string $serviceName = 'locale';
    
    /**
     * Register the shared `locale` service.
     *
     * Runtime options can override config when resolving the service manually,
     * mainly for tests or custom bootstraps.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $defaultOptions = $this->defaultOptions;
        
        $di->setShared($this->getName(), function (?array $options = null) use ($di, $defaultOptions) {
            
            $config = $di->getConfig();
            
            $options ??= $config->pathToArray('locale', $defaultOptions);
            return new Locale($options);
        });
    }
}
