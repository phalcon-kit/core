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
use Phalcon\Support\Helper\Str\Camelize;
use Phalcon\Support\Helper\Str\Uncamelize;
use PhalconKit\Modules\Cli\Task;

/**
 * Print a task's usage through `cli help <task>`.
 *
 * `cli help <task>` forwards to `<task> help` for any task in the current
 * module namespace, including application tasks this class does not list, so
 * applications only replace `$cliDoc` to list their own tasks. A task whose
 * handler class does not exist is forwarded to `router.notFound`, which reports
 * `Error: 404 - Not Found` and exits with status 1. The `cli` role needs this
 * task's actions (`['*']`) and the target task's `help` action.
 */
class HelpTask extends Task
{
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli help cache
  phalcon-kit cli help cron
  phalcon-kit cli help database
  phalcon-kit cli help data-life-cycle
  phalcon-kit cli help scaffold
  phalcon-kit cli help ts-scaffold
  phalcon-kit cli help user

DOC;

    /**
     * Never parse `help <task>` with this task's usage.
     *
     * The task name is the action, so parsing would reject every task that
     * `$cliDoc` does not list before it could be forwarded.
     */
    #[\Override]
    protected function shouldParseUsage(): bool
    {
        return false;
    }

    /**
     * Forward `cli help <task>` to `<task> help` for tasks without a method here.
     *
     * The dispatcher resolves `help <task>` to the `<task>Action` method; any
     * name not declared on this class reaches this method and is forwarded.
     *
     * @param string $method Undefined method name, such as `reportAction`.
     * @param array<array-key, mixed> $arguments Ignored dispatcher arguments.
     *
     * @throws \BadMethodCallException When `$method` is not an action name.
     * @throws DispatcherException if there is an error during the forwarding process
     */
    public function __call(string $method, array $arguments): null
    {
        if (!str_ends_with($method, 'Action') || $method === 'Action') {
            throw new \BadMethodCallException('Call to undefined method ' . static::class . '::' . $method . '()');
        }

        $this->forwardToTaskHelp(new Uncamelize()(substr($method, 0, -6), '-'));
        return null;
    }

    /**
     * Forward to `<task> help`, or to `router.notFound` for an unknown task.
     *
     * The handler class is resolved like the dispatcher does: the current
     * namespace, the camelized task name, and the handler suffix.
     *
     * @param string $task Dashed task name, such as `data-life-cycle`.
     *
     * @throws DispatcherException if there is an error during the forwarding process
     */
    protected function forwardToTaskHelp(string $task): void
    {
        $namespace = (string) $this->dispatcher->getNamespaceName();
        $handler = ($namespace !== '' ? rtrim($namespace, '\\') . '\\' : '')
            . new Camelize()($task, '_-')
            . $this->dispatcher->getHandlerSuffix();

        if (!class_exists($handler)) {
            $this->dispatcher->forward($this->config->pathToArray('router.notFound') ?? ['task' => 'error', 'action' => 'notFound']);
            return;
        }

        $this->dispatcher->forward(['task' => $task, 'action' => 'help']);
    }
    
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
        $this->forwardToTaskHelp('build');
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
        $this->forwardToTaskHelp('cron');
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
        $this->forwardToTaskHelp('cache');
    }

    public function databaseAction(): void
    {
        $this->forwardToTaskHelp('database');
    }

    public function dataLifeCycleAction(): void
    {
        $this->forwardToTaskHelp('data-life-cycle');
    }

    public function scaffoldAction(): void
    {
        $this->forwardToTaskHelp('scaffold');
    }

    public function tsScaffoldAction(): void
    {
        $this->forwardToTaskHelp('ts-scaffold');
    }

    public function userAction(): void
    {
        $this->forwardToTaskHelp('user');
    }
}
