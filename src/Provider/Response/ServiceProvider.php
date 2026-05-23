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

namespace PhalconKit\Provider\Response;

use PhalconKit\Di\DiInterface;
use PhalconKit\Http\Response;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'response';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
    
            $response = new Response();
            $response->setDI($di);
            
            $headers = $config->pathToArray('response.headers') ?? [];
            foreach ($headers as $name => $value) {
                $response->setHeader($name, $value);
            }

            return $response;
        });
    }
}
