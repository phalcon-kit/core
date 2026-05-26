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

namespace PhalconKit\Events;

use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Events\Manager;
use Phalcon\Events\ManagerInterface;
use PhalconKit\Di\DiInterface;
use PhalconKit\Exception\ConfigurationException;

/**
 * Attaches config-declared listeners to a Phalcon events manager.
 *
 * This helper keeps the bootstrap-level event attachment contract small and
 * explicit. Applications configure listeners by event type, then each listener
 * definition resolves to a class or DI service. Listener priorities use
 * Phalcon's native priority support; the helper enables
 * priorities before attaching so configured ordering works consistently across
 * MVC, CLI, WebSocket, and test bootstraps that share the same events manager.
 *
 * Supported listener definition forms:
 *
 * - `ListenerClass::class`
 * - `'listenerServiceName'`
 * - `['class' => ListenerClass::class, 'priority' => 200]`
 * - `['service' => 'listenerServiceName', 'priority' => 200]`
 *
 * Array definitions may set `enabled => false` to disable one entry without
 * removing it from merged configuration.
 */
final class ConfiguredEventListeners
{
    private function __construct()
    {
    }

    /**
     * Attach configured listeners to the provided manager.
     *
     * The expected config shape is an event-type map. Each value may be a
     * single listener definition or a list of listener definitions:
     *
     * ```php
     * [
     *     'dispatch' => [
     *         ['class' => App\Listener\Security::class, 'priority' => 200],
     *         ['service' => 'auditDispatchListener', 'priority' => 100],
     *     ],
     * ]
     * ```
     *
     * @param DiInterface $di Container used to resolve listener services and
     *     inject DI into listener objects that implement Phalcon's
     *     `InjectionAwareInterface`.
     * @param ManagerInterface $eventsManager Events manager that receives the
     *     listener attachments.
     * @param array<array-key, mixed> $listeners Event-type map from config.
     *
     * @throws ConfigurationException When an event type, listener definition,
     *     listener class, listener service, or priority is invalid.
     */
    public static function attach(DiInterface $di, ManagerInterface $eventsManager, array $listeners): void
    {
        if ($listeners === []) {
            return;
        }

        $eventsManager->enablePriorities(true);

        foreach ($listeners as $eventType => $definitions) {
            if (!is_string($eventType) || trim($eventType) === '') {
                throw new ConfigurationException('Configured event listener groups must use a non-empty event type.');
            }

            foreach (self::normalizeDefinitions($definitions) as $index => $definition) {
                $priority = Manager::DEFAULT_PRIORITY;
                $listener = self::resolveDefinition($di, $definition, $eventType, $index, $priority);
                if ($listener === null) {
                    continue;
                }

                $eventsManager->attach($eventType, $listener, $priority);
            }
        }
    }

    /**
     * Normalize one event-type value to a list of listener definitions.
     *
     * @param mixed $definitions Config value for one event type.
     * @return array<int|string, mixed>
     */
    private static function normalizeDefinitions(mixed $definitions): array
    {
        if (!is_array($definitions)) {
            return [$definitions];
        }

        if (self::isAssociativeListenerDefinition($definitions)) {
            return [$definitions];
        }

        return $definitions;
    }

    /**
     * Detect whether an array is a single listener definition.
     *
     * @param array<array-key, mixed> $definition Candidate definition.
     */
    private static function isAssociativeListenerDefinition(array $definition): bool
    {
        return array_key_exists('class', $definition)
            || array_key_exists('service', $definition)
            || array_key_exists('enabled', $definition)
            || array_key_exists('priority', $definition);
    }

    /**
     * Resolve one listener definition and expose its priority by reference.
     *
     * @param mixed $definition Listener definition from config.
     * @param int|string $index Original index inside the event-type config.
     * @param int $priority Priority updated from the definition.
     * @return object|callable|null Resolved listener, or null when disabled.
     */
    private static function resolveDefinition(
        DiInterface $di,
        mixed $definition,
        string $eventType,
        int|string $index,
        int &$priority
    ): object|callable|null {
        if (is_array($definition)) {
            if (($definition['enabled'] ?? true) === false) {
                return null;
            }

            $priority = self::resolvePriority($definition['priority'] ?? Manager::DEFAULT_PRIORITY, $eventType, $index);

            if (array_key_exists('service', $definition)) {
                return self::listenerFromService($di, $definition['service'], $eventType, $index);
            }

            if (array_key_exists('class', $definition)) {
                $arguments = $definition['arguments'] ?? [];
                if (!is_array($arguments)) {
                    throw new ConfigurationException(sprintf(
                        'Configured event listener "%s" for event "%s" must define "arguments" as an array.',
                        (string)$index,
                        $eventType
                    ));
                }

                return self::listenerFromClass($di, $definition['class'], $eventType, $index, $arguments);
            }

            throw new ConfigurationException(sprintf(
                'Configured event listener "%s" for event "%s" must define "class" or "service".',
                (string)$index,
                $eventType
            ));
        }

        if (is_string($definition)) {
            return self::listenerFromString($di, $definition, $eventType, $index);
        }

        throw new ConfigurationException(sprintf(
            'Configured event listener "%s" for event "%s" must be a class name, DI service name, or array definition.',
            (string)$index,
            $eventType
        ));
    }

    /**
     * Resolve and validate a listener priority.
     */
    private static function resolvePriority(mixed $priority, string $eventType, int|string $index): int
    {
        if (is_int($priority)) {
            return $priority;
        }

        if (is_string($priority) && preg_match('/^-?\d+$/', $priority) === 1) {
            return (int)$priority;
        }

        throw new ConfigurationException(sprintf(
            'Configured event listener "%s" for event "%s" must define "priority" as an integer.',
            (string)$index,
            $eventType
        ));
    }

    /**
     * Resolve a shorthand string as a class name or DI service name.
     */
    private static function listenerFromString(
        DiInterface $di,
        string $definition,
        string $eventType,
        int|string $index
    ): object|callable {
        if (class_exists($definition)) {
            return self::listenerFromClass($di, $definition, $eventType, $index);
        }

        if ($di->has($definition)) {
            return self::listenerFromService($di, $definition, $eventType, $index);
        }

        throw new ConfigurationException(sprintf(
            'Configured event listener "%s" for event "%s" must be an existing class or DI service.',
            $definition,
            $eventType
        ));
    }

    /**
     * Resolve a listener from a configured DI service.
     */
    private static function listenerFromService(
        DiInterface $di,
        mixed $service,
        string $eventType,
        int|string $index
    ): object|callable {
        if (!is_string($service) || trim($service) === '') {
            throw new ConfigurationException(sprintf(
                'Configured event listener "%s" for event "%s" must define "service" as a non-empty string.',
                (string)$index,
                $eventType
            ));
        }

        if (!$di->has($service)) {
            throw new ConfigurationException(sprintf(
                'Configured event listener service "%s" for event "%s" is not registered.',
                $service,
                $eventType
            ));
        }

        return self::finalizeListener($di, $di->getShared($service), $eventType, $index);
    }

    /**
     * Instantiate a configured listener class.
     *
     * @param array<array-key, mixed> $arguments Constructor arguments.
     */
    private static function listenerFromClass(
        DiInterface $di,
        mixed $class,
        string $eventType,
        int|string $index,
        array $arguments = []
    ): object|callable {
        if (!is_string($class) || !class_exists($class)) {
            throw new ConfigurationException(sprintf(
                'Configured event listener "%s" for event "%s" must define "class" as an existing class name.',
                (string)$index,
                $eventType
            ));
        }

        return self::finalizeListener($di, new $class(...$arguments), $eventType, $index);
    }

    /**
     * Validate the listener and inject the DI container when supported.
     */
    private static function finalizeListener(
        DiInterface $di,
        mixed $listener,
        string $eventType,
        int|string $index
    ): object|callable {
        if ($listener instanceof InjectionAwareInterface) {
            $listener->setDI($di);
        }

        if (is_object($listener) || is_callable($listener)) {
            return $listener;
        }

        throw new ConfigurationException(sprintf(
            'Configured event listener "%s" for event "%s" must resolve to an object or callable; got "%s".',
            (string)$index,
            $eventType,
            get_debug_type($listener)
        ));
    }
}
