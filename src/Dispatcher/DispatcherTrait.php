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

namespace PhalconKit\Dispatcher;

use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Cli\Dispatcher as CliDispatcher;

/**
 * Shared dispatcher behavior for MVC and CLI dispatchers.
 *
 * The trait normalizes two framework concerns across dispatcher types:
 * preserving only positional action arguments when invoking handlers, and
 * preventing event-driven forwards from cycling back to the current target.
 *
 * @see https://docs.phalcon.io/5.13/dispatcher/
 */
trait DispatcherTrait
{
    abstract public function getNamespaceName(): ?string;
    
    abstract public function getModuleName(): ?string;

    abstract public function setModuleName(?string $moduleName = null): void;
    
    abstract public function getActionName(): string;
    
    abstract public function getParams(): array;
    
    abstract public function getHandlerClass(): string;
    
    abstract public function getHandlerSuffix(): string;

    abstract public function getActionSuffix(): string;
    
    abstract public function getActiveMethod(): string;
    
    /**
     * Invoke a controller or task action with positional parameters only.
     *
     * Phalcon stores dispatch params as an array that may contain named metadata.
     * Only integer-keyed entries are passed to action method arguments so route
     * metadata cannot accidentally shift the method signature.
     *
     * @param mixed $handler Controller or task instance selected by Phalcon.
     * @param string $actionMethod Method name to call on the handler.
     * @param array<int|string, mixed> $params Dispatch parameters.
     *
     * @return mixed Action return value.
     */
    public function callActionMethod(mixed $handler, string $actionMethod, array $params = []): mixed
    {
        return call_user_func_array(
            [$handler, $actionMethod],
            array_filter($params, 'is_int', ARRAY_FILTER_USE_KEY)
        );
    }
    
    /**
     * Forward to another target, optionally skipping cyclic forwards.
     *
     * Null forward parts are stripped before delegating to Phalcon. When
     * `$preventCycle` is true, forwarding only happens if at least one target
     * part differs from the current dispatch state.
     *
     * @param array<string, mixed> $forward Forward target parts.
     * @param bool $preventCycle Whether identical forwards should be ignored.
     *
     * @return void
     */
    public function forward(array $forward, bool $preventCycle = false): void
    {
        $forward = $this->unsetForwardNullParts($forward);
        
        if (!$preventCycle || $this->canForward($forward)) {
            if (isset($forward['module'])) {
                $this->setModuleName($forward['module']);
            }

            parent::forward($forward);
        }
    }
    
    /**
     * Determine whether a forward target differs from the current dispatch.
     *
     * @param array<array-key, mixed> $forward Forward target parts.
     */
    public function canForward(array $forward): bool
    {
        $parts = [
            'namespace' => $this->getNamespaceName(),
            'module' => $this->getModuleName(),
            'action' => $this->getActionName(),
            'params' => $this->getParams(),
        ];
        if (array_any($parts, fn(array|string|null $current, string $part): bool => isset($forward[$part]) && $current !== $forward[$part])) {
            return true;
        }
        
        return $this->canForwardHandler($forward);
    }
    
    /**
     * Determine whether the dispatcher-specific handler target changes.
     *
     * MVC dispatchers compare controllers; CLI dispatchers compare tasks.
     *
     * @param array<string, mixed> $forward Forward target parts.
     */
    private function canForwardHandler(array $forward): bool
    {
        if ($this->canForwardController($forward['controller'] ?? null)) {
            return true;
        }
        
        if ($this->canForwardTask($forward['task'] ?? null)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Determine whether an MVC forward points to a different controller.
     */
    private function canForwardController(?string $controller = null): bool
    {
        if ($this instanceof MvcDispatcher && isset($controller) && $this->getControllerName() !== $controller) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Determine whether a CLI forward points to a different task.
     */
    private function canForwardTask(?string $task = null): bool
    {
        if ($this instanceof CliDispatcher && isset($task) && $this->getTaskName() !== $task) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove null parts from a forward target before delegating to Phalcon.
     *
     * @param array<string, mixed> $forward Forward target parts.
     * @param array<int, string>|null $parts Forward keys to inspect. Defaults
     *     to the common MVC and CLI target keys.
     *
     * @return array<string, mixed> Forward target with null parts removed.
     */
    public function unsetForwardNullParts(array $forward, ?array $parts = null): array
    {
        $parts ??= [
            'namespace',
            'module',
            'task',
            'controller',
            'action',
            'params',
        ];
        
        foreach ($parts as $part) {
            if (is_null($forward[$part] ?? null)) {
                unset($forward[$part]);
            }
        }
        
        return $forward;
    }
    
    /**
     * Export the active dispatcher state for diagnostics and debug responses.
     *
     * @return array<string, mixed> Current namespace, module, handler, action,
     *     parameters, and dispatcher-specific previous route state.
     */
    public function toArray(): array
    {
        $ret = [
            'namespace' => $this->getNamespaceName(),
            'module' => $this->getModuleName(),
            'action' => $this->getActionName(),
            'params' => $this->getParams(),
            'handlerClass' => $this->getHandlerClass(),
            'handlerSuffix' => $this->getHandlerSuffix(),
            'activeMethod' => $this->getActiveMethod(),
            'actionSuffix' => $this->getActionSuffix(),
        ];
        
        if ($this instanceof MvcDispatcher) {
            $ret['controller'] = $this->getControllerName();
            $ret['previousNamespace'] = $this->getPreviousNamespaceName();
            $ret['previousController'] = $this->getPreviousControllerName();
            $ret['previousAction'] = $this->getPreviousActionName();
        }
        
        if ($this instanceof CliDispatcher) {
            $ret['task'] = $this->getTaskName();
            $ret['taskSuffix'] = $this->getTaskSuffix();
        }
        
        return $ret;
    }
}
