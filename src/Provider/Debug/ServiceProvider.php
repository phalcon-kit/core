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

namespace PhalconKit\Provider\Debug;

use PhalconKit\Di\DiInterface;
use Phalcon\Support\Version;
use PhalconKit\Bootstrap;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Debug;
use PhalconKit\Support\Php;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'debug';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $causeCyclicError = $this->causeCyclicError();
        
        $di->setShared($this->getName(), function () use ($di, $causeCyclicError) {
            
            $bootstrap = $di->getTyped('bootstrap', Bootstrap::class);
            
            $config = $di->getConfig();
            
            $isEnabled = $config->path('app.debug') || $config->path('debug.enable');
            
            Php::debug($isEnabled);
            $debug = new Debug();
            
            if ($isEnabled && !$causeCyclicError && $bootstrap->isMvc()) {
                $debugConfig = $config->pathToArray('debug') ?? [];
                
                $debug->listen($debugConfig['exceptions'] ?? true, $debugConfig['lowSeverity'] ?? false);
                $debug->setBlacklist($debugConfig['blacklist'] ?? []);
                $debug->setShowFiles($debugConfig['showFiles'] ?? true);
                $debug->setShowBackTrace($debugConfig['showBackTrace'] ?? true);
                $debug->setShowFileFragment($debugConfig['showFileFragment'] ?? true);
                
                $uri = $debugConfig['uri'] ?? null;
                if (is_string($uri)) {
                    $debug->setUri($uri);
                }
            }
            
            return $debug;
        });
    }
    
    public function causeCyclicError(): bool
    {
        return
            version_compare(PHP_VERSION, '8.0.0', '>=') &&
            version_compare((new Version())->get(), '5.0.0', '<');
    }
}
