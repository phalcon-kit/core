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

class Logger extends Injectable
{
    /**
     * Check if the logger is currently enabled or not from the config
     */
    public function isEnabled(): bool
    {
        return ($this->config->path('app.logger') || $this->config->path('logger.enable'))
            && $this->config->path('logger.dispatcher');
    }
    
    /**
     * This action is executed before execute any action in the application
     * Keeping a log of the dispatch event
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
