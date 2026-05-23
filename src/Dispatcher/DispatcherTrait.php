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
     * {@inheritDoc}
     * The string typed keys are not passed to the action method arguments
     * Only the int keys will be passed
     *
     * @param mixed $handler
     * @return mixed
     */
    public function callActionMethod(mixed $handler, string $actionMethod, array $params = []): mixed
    {
        return call_user_func_array(
            [$handler, $actionMethod],
            array_filter($params, 'is_int', ARRAY_FILTER_USE_KEY)
        );
    }
    
    /**
     * Extending forwarding event to prevent cyclic routing when forwarding under dispatcher events
     * {@inheritDoc}
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
     * Check whether the forward attribute can be forwarded
     * we do additional checks to prevent dispatcher cycling
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
     * Check whether the handler is changed or not
     * depending on the dispatcher
     * MVC: controller
     * CLI: task
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
     * Check whether the controller is changed
     */
    private function canForwardController(?string $controller = null): bool
    {
        if ($this instanceof MvcDispatcher && isset($controller) && $this->getControllerName() !== $controller) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check whether the task is changed
     */
    private function canForwardTask(?string $task = null): bool
    {
        if ($this instanceof CliDispatcher && isset($task) && $this->getTaskName() !== $task) {
            return true;
        }
        
        return false;
    }
    
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
