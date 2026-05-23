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

namespace PhalconKit\Modules\Cli\Tasks;

use Phalcon\Dispatcher\Exception as DispatcherException;
use PhalconKit\Modules\Cli\Task;

class HelpTask extends Task
{
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli help cache
  phalcon-kit cli help cron
  phalcon-kit cli help database
  phalcon-kit cli help data-life-cycle
  phalcon-kit cli help scaffold
  phalcon-kit cli help test
  phalcon-kit cli help user

DOC;
    
    /**
     * Build Action
     *
     * This method executes the build action by forwarding the request to the build task's help action.
     *
     * @return void
     * @throws DispatcherException if there is an error during the forwarding process
     */
    public function buildAction(): void
    {
        $this->dispatcher->forward(['task' => 'build', 'action' => 'help']);
    }
    
    /**
     * Cron Action
     *
     * This method executes the cron action by forwarding the request to the cron task's help action.
     *
     * @return void
     * @throws DispatcherException if there is an error during the forwarding process
     */
    public function cronAction(): void
    {
        $this->dispatcher->forward(['task' => 'cron', 'action' => 'help']);
    }
    
    /**
     * Cache Action
     *
     * This method executes the cache action by forwarding the request to the cache task's help action.
     *
     * @return void
     * @throws DispatcherException if there is an error during the forwarding process
     */
    public function cacheAction(): void
    {
        $this->dispatcher->forward(['task' => 'cache', 'action' => 'help']);
    }
}
