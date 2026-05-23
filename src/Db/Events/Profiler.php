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

use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Events\EventInterface;
use PhalconKit\Di\Injectable;

/**
 * Database event listener that feeds executed queries into the profiler.
 *
 * Attach this class to a database connection events manager to start a profile
 * before each query and stop it afterwards. Profiling is controlled by
 * `app.profiler`, falling back to `profiler.enable`, so applications can keep
 * the listener registered while disabling collection in production.
 */
class Profiler extends Injectable
{
    /**
     * Determine whether query profiling is enabled by configuration.
     */
    public function isEnabled(): bool
    {
        return (bool)$this->config->path(
            'app.profiler',
            $this->config->path(
                'profiler.enable',
                false
            )
        );
    }
    
    /**
     * Start profiling the SQL statement about to be executed.
     *
     * Stopped events are ignored so listeners earlier in the chain can cancel
     * profiling together with the query.
     *
     * @param EventInterface $event Database `beforeQuery` event.
     * @param AbstractAdapter $connection Connection that is about to execute
     *     the statement.
     */
    public function beforeQuery(EventInterface $event, AbstractAdapter $connection): void
    {
        if ($this->isEnabled()) {
            if (!$event->isStopped()) {
                $this->profiler->startProfile(
                    $connection->getSQLStatement(),
                    $connection->getSqlVariables(),
                    $connection->getSQLBindTypes(),
                );
            }
        }
    }
    
    /**
     * Stop the active query profile after execution.
     *
     * @scrutinizer ignore-unused
     *
     * @param EventInterface $event Database `afterQuery` event.
     * @param AbstractAdapter $connection Connection that executed the statement.
     */
    public function afterQuery(EventInterface $event, AbstractAdapter $connection): void
    {
        if ($this->isEnabled()) {
            $this->profiler->stopProfile();
        }
    }
}
