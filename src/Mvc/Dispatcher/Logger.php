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

namespace PhalconKit\Mvc\Dispatcher;

use Phalcon\Events\Event;
use Phalcon\Logger\Exception as LoggerException;
use PhalconKit\Di\Injectable;
use PhalconKit\Dispatcher\DispatcherInterface;

/**
 * Dispatcher listener that logs route resolution metadata.
 *
 * The listener writes one structured log entry before the dispatch loop starts
 * when both the global logger flag and dispatcher logger flag are enabled.
 */
class Logger extends Injectable
{
    /**
     * Determine whether dispatcher logging is enabled by configuration.
     */
    public function isEnabled(): bool
    {
        return ($this->config->path('app.logger') || $this->config->path('logger.enable'))
            && $this->config->path('logger.dispatcher');
    }
    
    /**
     * Log the current dispatcher state before action execution.
     *
     * @param Event $event Dispatch event emitted by Phalcon.
     * @param DispatcherInterface $dispatcher Active PhalconKit dispatcher.
     *
     * @throws LoggerException When Phalcon cannot write the dispatch log entry.
     */
    public function beforeDispatchLoop(Event $event, DispatcherInterface $dispatcher): void
    {
        if ($this->isEnabled()) {
            if ($this->config->path('logger.dispatcher')) {
                $log = json_encode([
                    'type' => 'dispatch',
                    'key' => $this->identity->getKey(),
                    'userId' => $this->identity->getUserId(),
                    'userAsId' => $this->identity->getUserAsId(),
                    'meta' => [
                        'dispatcher' => $dispatcher->toArray(),
                    ],
                ]);
                
                if (!empty($log)) {
                    $this->logger->info($log);
                }
            }
        }
    }
}
