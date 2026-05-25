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

/**
 * Registers the debug helper service.
 *
 * Debug mode is enabled when either `app.debug` or `debug.enable` is truthy.
 * When enabled in MVC mode, the provider attaches Phalcon's debug listener and
 * applies display options from the `debug` config section. CLI and WebSocket
 * modes still receive a debug service instance, but do not attach the MVC debug
 * listener.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'debug';
    
    /**
     * Register the shared `debug` service.
     *
     * The provider also toggles PHP debug display behavior through
     * `PhalconKit\Support\Php::debug()`, keeping PHP runtime debug flags aligned
     * with the framework debug service.
     */
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
    
    /**
     * Detect an old Phalcon/PHP combination that cannot safely attach debug.
     *
     * Phalcon versions before 5 can trigger cyclic debug errors on PHP 8+. The
     * provider keeps the guard isolated so tests can assert the compatibility
     * decision and future Phalcon upgrades can remove or revise it cleanly.
     *
     * @return bool True when the runtime should skip debug listener attachment.
     */
    public function causeCyclicError(): bool
    {
        return
            version_compare(PHP_VERSION, '8.0.0', '>=') &&
            version_compare((new Version())->get(), '5.0.0', '<');
    }
}
