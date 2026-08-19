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

namespace PhalconKit\Db\Events;

use Phalcon\Contracts\Events\Event as EventContract;
use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Logger\Exception as LoggerException;
use PhalconKit\Di\Injectable;

/**
 * Responsible for logging database query events.
 */
class Logger extends Injectable
{
    public bool $inProgress = false;
    
    /**
     * Executes before a database query is executed.
     *
     * @param EventContract $event The event object.
     * @param AbstractAdapter $connection The database connection object.
     * @return void
     * @throws LoggerException If Phalcon cannot write the query log entry.
     */
    public function beforeQuery(EventContract $event, AbstractAdapter $connection): void
    {
        if ($this->config->path('logger.enable') || $this->config->path('app.logger')) {
            if ($this->config->path('loggers.database.enable')) {
                if (!$this->inProgress) {
                    $this->inProgress = true;
                    $userId = $this->identity->getUserId() ?: null;
                    $userAsId = $this->identity->getUserAsId() ?: null;
                    
                    $log = json_encode([
                        'type' => 'query',
                        'userId' => $userId,
                        'userAsId' => $userAsId,
                        'event' => [
                            'type' => $event->getType(),
                            'data' => $event->getData(),
                        ],
                        'meta' => [
                            'sqlStatement' => $connection->getSQLStatement(),
                            'sqlVariables' => $connection->getSQLVariables(),
                        ],
                    ]);
                    
                    if (!empty($log)) {
                        $this->loggers->get('database')->info($log);
                    }
                    
                    $this->inProgress = false;
                }
            }
        }
    }

    /**
     * Log that Phalcon detected a lost PDO connection.
     *
     * The event is emitted before an enabled adapter attempts its single
     * automatic reconnect. Query text and bind values are intentionally omitted
     * because the lost-connection payload can occur on sensitive operations.
     *
     * @param EventContract $event Connection-lost event and reconnect context.
     * @param AbstractAdapter $connection Connection whose state was lost.
     * @throws LoggerException If Phalcon cannot write the database log entry.
     */
    public function connectionLost(EventContract $event, AbstractAdapter $connection): void
    {
        if (
            !($this->config->path('logger.enable') || $this->config->path('app.logger'))
            || !$this->config->path('loggers.database.enable')
            || $this->inProgress
        ) {
            return;
        }

        $this->inProgress = true;
        try {
            $log = json_encode([
                'type' => 'connectionLost',
                'event' => [
                    'type' => $event->getType(),
                ],
                'meta' => [
                    'connectionId' => $connection->getConnectionId(),
                ],
            ]);

            if (!empty($log)) {
                $this->loggers->get('database')->warning($log);
            }
        } finally {
            $this->inProgress = false;
        }
    }
}
