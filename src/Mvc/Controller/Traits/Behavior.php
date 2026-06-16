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

namespace PhalconKit\Mvc\Controller\Traits;

use Phalcon\Contracts\Events\Manager as EventsManagerContract;
use Phalcon\Di\Injectable;
use Phalcon\Dispatcher\AbstractDispatcher;
use Phalcon\Events\Manager;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use PhalconKit\Acl\PermissionName;
use PhalconKit\Mvc\Controller\Attributes\PermissionAttributeResolver;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;

trait Behavior
{
    use AbstractBehavior;
    
    use AbstractInjectable;
    
    public function beforeExecuteRoute(): void
    {
        $eventsManager = $this->getOrCreateEventsManager();
        $eventsManager->enablePriorities(true);
        
        // Native response collection changes the shape returned by event
        // dispatching, so keep it disabled until a V2 controller contract
        // explicitly supports multiple behavior responses.
        
        // retrieve events based on the config roles and features
        $permissions = $this->config->pathToArray('permissions') ?? [];
        if ($this->usesControllerAttributes()) {
            $permissions = PermissionAttributeResolver::mergePermissions(
                $permissions,
                PermissionAttributeResolver::forController($this)
            );
        }
        $featureList = $permissions['features'] ?? [];
        $roleList = $permissions['roles'] ?? [];
        $handlerCandidates = $this->getBehaviorHandlerCandidates();
        $actionCandidates = $this->getBehaviorActionCandidates();
        $modelName = method_exists($this, 'getModelName') ? $this->getModelName() : null;
        
        foreach ($roleList as $role => $rolePermission) {
            // do not attach other roles behaviors
            if ($role !== 'everyone' && !$this->identity->hasRole([$role])) {
                continue;
            }
            
            if (isset($rolePermission['features'])) {
                foreach ($rolePermission['features'] as $feature) {
                    $rolePermission = array_merge_recursive($rolePermission, $featureList[$feature] ?? []);
                    // Recursive merges can duplicate feature entries; keep the
                    // current behavior until permission merge semantics are
                    // defined centrally.
                }
            }
            
            $behaviorsContext = $rolePermission['behaviors'] ?? [];
            $this->attachConfiguredBehaviors($behaviorsContext, $handlerCandidates, $modelName);

            $behaviorActionsContext = $rolePermission['behaviorActions'] ?? [];
            $this->attachConfiguredActionBehaviors(
                $behaviorActionsContext,
                $handlerCandidates,
                $actionCandidates,
                $modelName
            );
        }
    }
    
    /**
     * Attach a behavior to the object.
     *
     * @param string $eventClass The behavior to attach.
     * @param string|null $eventType The event type to attach the behavior to. If null, the behavior will be attached to the default event type.
     * @param int|null $priority The priority of the behavior. If null, the behavior will be attached with the default priority.
     *
     * @return void
     */
    public function attachBehavior(string $eventClass, ?string $eventType = null, ?int $priority = null): void
    {
        $event = new $eventClass();
        
        if ($event instanceof Injectable) {
            $event->setDI($this->getDI());
        }
        
        $eventType = $event->eventType ?? $eventType ?? 'rest';
        $priority = $event->priority ?? $priority ?? Manager::DEFAULT_PRIORITY;
        $this->getOrCreateEventsManager()->attach($eventType, $event, $priority);
    }
    
    /**
     * Attach multiple behaviors to the object.
     *
     * @param array $behaviors An array of behaviors to attach.
     * @param string|null $eventType The event type to attach the behaviors to. If null, the behaviors will be attached to all event types.
     * @param int|null $priority The priority of the behaviors. If null, the behaviors will be attached with the default priority.
     *
     * @return void
     */
    public function attachBehaviors(array $behaviors = [], ?string $eventType = null, ?int $priority = null): void
    {
        foreach ($behaviors as $behavior) {
            $this->attachBehavior($behavior, $eventType, $priority);
        }
    }

    protected function getOrCreateEventsManager(): EventsManagerContract
    {
        $eventsManager = $this->getEventsManager();
        if ($eventsManager instanceof EventsManagerContract) {
            return $eventsManager;
        }

        $di = $this->getDI();
        if ($di->has('eventsManager')) {
            $eventsManager = $di->getShared('eventsManager');
        }

        if (!$eventsManager instanceof Manager) {
            $eventsManager = new Manager();
        }

        $this->setEventsManager($eventsManager);
        return $eventsManager;
    }

    /**
     * Attach legacy, non-action-scoped behavior config for this controller/model.
     *
     * @param array<string|int, mixed> $behaviorsContext Permission behavior map.
     * @param array<int, string> $handlerCandidates Controller class/name aliases.
     */
    private function attachConfiguredBehaviors(
        array $behaviorsContext,
        array $handlerCandidates,
        ?string $modelName
    ): void {
        foreach ($behaviorsContext as $className => $behaviors) {
            if (is_int($className) || in_array($className, $handlerCandidates, true)) {
                $this->attachBehaviors((array)$behaviors, 'rest');
            }

            if ($modelName !== null && $modelName === $className) {
                $this->attachBehaviors((array)$behaviors, 'model');
            }
        }
    }

    /**
     * Attach action-scoped controller/model behavior config for this request.
     *
     * @param array<string|int, mixed> $behaviorActionsContext Action behavior map.
     * @param array<int, string> $handlerCandidates Controller class/name aliases.
     * @param array<int, string> $actionCandidates Current action aliases.
     */
    private function attachConfiguredActionBehaviors(
        array $behaviorActionsContext,
        array $handlerCandidates,
        array $actionCandidates,
        ?string $modelName
    ): void {
        foreach ($behaviorActionsContext as $className => $actionBehaviors) {
            $eventType = 'rest';
            if (is_int($className) || in_array($className, $handlerCandidates, true)) {
                $matches = true;
            }
            elseif ($modelName !== null && $modelName === $className) {
                $matches = true;
                $eventType = 'model';
            }
            else {
                $matches = false;
            }

            if (!$matches || !is_array($actionBehaviors)) {
                continue;
            }

            foreach ($actionBehaviors as $action => $behaviors) {
                $action = (string)$action;
                if (
                    $action !== '*'
                    && !in_array($action, $actionCandidates, true)
                    && !in_array(PermissionName::action($action), $actionCandidates, true)
                ) {
                    continue;
                }

                $this->attachBehaviors((array)$behaviors, $eventType);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function getBehaviorHandlerCandidates(): array
    {
        $dispatcher = $this->getBehaviorDispatcher();
        $routeName = null;

        if ($dispatcher instanceof MvcDispatcher) {
            $routeName = $dispatcher->getControllerName();
        }

        return PermissionName::handlerCandidates($this::class, $routeName, 'Controller');
    }

    /**
     * @return array<int, string>
     */
    private function getBehaviorActionCandidates(): array
    {
        $dispatcher = $this->getBehaviorDispatcher();
        if ($dispatcher === null) {
            return ['*'];
        }

        return PermissionName::actionCandidates($dispatcher->getActionName());
    }

    private function getBehaviorDispatcher(): ?AbstractDispatcher
    {
        $di = $this->getDI();
        if (!$di->has('dispatcher')) {
            return null;
        }

        $dispatcher = $di->getShared('dispatcher');
        return $dispatcher instanceof AbstractDispatcher ? $dispatcher : null;
    }

    /**
     * Determine whether controller attributes should augment permission config.
     */
    private function usesControllerAttributes(): bool
    {
        return (bool)($this->config->path('acl.attributes', true) ?? true);
    }
}
