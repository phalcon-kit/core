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

namespace PhalconKit\Provider\Swoole;

use PhalconKit\Di\DiInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Provider\AbstractServiceProvider;
use Swoole\WebSocket\Server;

/**
 * Registers the Swoole WebSocket server service.
 *
 * The provider requires the Swoole extension and builds a
 * `Swoole\WebSocket\Server` from the `swoole` config section. Conservative
 * defaults are applied for host, port, worker count, max connections, heartbeat
 * timing, logging, and trace flags.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'swoole';
    
    /**
     * Register the shared `swoole` service.
     *
     * @throws ServiceException When the Swoole extension or required constants
     *     are not available in the current PHP runtime.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            if (!defined('SWOOLE_LOG_WARNING') || !extension_loaded('swoole')) {
                throw new ServiceException('Swoole not available');
            }
            
            $config = $di->getConfig();
            
            $swooleConfig = $config->pathToArray('swoole') ?? [];
            
            $swooleConfig['host'] ??= '0.0.0.0';
            $swooleConfig['port'] ??= 8080;
            
            $swooleConfig['settings'] ??= [];
            $swooleConfig['settings']['worker_num'] ??= 1;
            $swooleConfig['settings']['max_conn'] ??= 1000;
            $swooleConfig['settings']['daemonize'] ??= false;
            $swooleConfig['settings']['heartbeat_check_interval'] ??= 60;
            $swooleConfig['settings']['heartbeat_idle_time'] ??= 120;
            $swooleConfig['settings']['log_level'] ??= SWOOLE_LOG_WARNING;
            $swooleConfig['settings']['trace_flags'] ??= 0;

            $server = new Server($swooleConfig['host'], (int)$swooleConfig['port']);
            $server->set($swooleConfig['settings']);
            
            return $server;
        });
    }
}
