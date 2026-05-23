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

class ServiceProvider extends AbstractServiceProvider
{
    /**
     * Default values if nothing is provided from the config
     */
    public array $defaultOptions = [
        'default' => 'en',
        'sessionKey' => 'phalcon-kit-locale',
        'mode' => Locale::MODE_DEFAULT,
        'allowed' => ['en'],
    ];
    
    protected string $serviceName = 'locale';
    
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
