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

namespace PhalconKit\Provider\Cookies;

use PhalconKit\Di\DiInterface;
use Phalcon\Http\Response\Cookies;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'cookies';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?bool $useEncryption = null, ?string $signKey = null) use ($di) {
    
            $config = $di->getConfig();
            $options = $config->pathToArray('cookies', []);
    
            $useEncryption ??= $options['useEncryption'] ?? true;
            $signKey ??= $options['signKey'] ?? null;
            
            return new Cookies($useEncryption, $signKey);
        });
    }
}
