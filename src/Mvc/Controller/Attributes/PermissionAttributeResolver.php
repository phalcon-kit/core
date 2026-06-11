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

namespace PhalconKit\Mvc\Controller\Attributes;

use PhalconKit\Acl\PermissionName;
use ReflectionClass;
use ReflectionMethod;

/**
 * Compiles controller PHP attributes into PhalconKit permission config arrays.
 *
 * The resolver deliberately returns the existing `permissions` shape instead of
 * introducing a second policy system. Dispatcher security, ACL compilation, and
 * controller behavior attachment can merge the returned fragment into
 * application config and keep using the established enforcement paths.
 */
final class PermissionAttributeResolver
{
    /**
     * @var array<class-string, array<string, mixed>>
     */
    private static array $cache = [];

    /**
     * Compile attributes declared on a controller class and its public methods.
     *
     * @param object|string $controller Controller instance or class name.
     *
     * @return array<string, mixed> Permission fragment containing `features`,
     *     `roles`, `controllers`, and action-scoped `behaviorActions` entries.
     */
    public static function forController(object|string $controller): array
    {
        $className = is_object($controller) ? $controller::class : ltrim($controller, '\\');
        if (!class_exists($className)) {
            return [];
        }
        /** @var class-string $className */

        if (isset(self::$cache[$className])) {
            return self::$cache[$className];
        }

        $permissions = [];
        $reflection = new ReflectionClass($className);

        self::collectAttributes($permissions, $reflection, $className, ['*']);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $hasPolicyAttributes = $method->getAttributes(AllowRoles::class) !== []
                || $method->getAttributes(PermissionFeature::class) !== []
                || $method->getAttributes(AttachBehavior::class) !== [];

            if (!$hasPolicyAttributes) {
                continue;
            }

            self::collectAttributes(
                $permissions,
                $method,
                $className,
                [PermissionName::actionFromMethod($method->getName())]
            );
        }

        self::$cache[$className] = $permissions;
        return $permissions;
    }

    /**
     * Merge a permission fragment into configured permissions.
     *
     * List values are appended and de-duplicated; associative values are merged
     * recursively. This matches PhalconKit's additive config expectations without
     * mutating the original config object.
     *
     * @param array<string, mixed> $base Existing permission config.
     * @param array<string, mixed> $fragment Attribute-derived permission config.
     *
     * @return array<string, mixed> Merged permissions.
     */
    public static function mergePermissions(array $base, array $fragment): array
    {
        return self::mergeValue($base, $fragment);
    }

    /**
     * @param ReflectionClass<object>|ReflectionMethod $reflection
     * @param class-string $controllerClass
     * @param array<int, string> $defaultActions
     */
    private static function collectAttributes(
        array &$permissions,
        ReflectionClass|ReflectionMethod $reflection,
        string $controllerClass,
        array $defaultActions
    ): void {
        foreach (self::attributeInstances($reflection, AllowRoles::class) as $attribute) {
            $actions = PermissionName::normalizeAttributeActions($attribute->actions, $defaultActions);
            self::addControllerAccess($permissions, 'roles', $attribute->roles, $controllerClass, $actions);
        }

        foreach (self::attributeInstances($reflection, PermissionFeature::class) as $attribute) {
            $actions = PermissionName::normalizeAttributeActions($attribute->actions, $defaultActions);
            self::addControllerAccess($permissions, 'features', $attribute->features, $controllerClass, $actions);
        }

        foreach (self::attributeInstances($reflection, AttachBehavior::class) as $attribute) {
            $actions = PermissionName::normalizeAttributeActions($attribute->actions, $defaultActions);
            $roles = $attribute->roles;
            $features = $attribute->features;

            if ($roles === [] && $features === []) {
                $roles = ['everyone'];
            }

            self::addActionBehaviors($permissions, 'roles', $roles, $controllerClass, $actions, $attribute->behaviors);
            self::addActionBehaviors($permissions, 'features', $features, $controllerClass, $actions, $attribute->behaviors);
        }
    }

    /**
     * @template T of object
     *
     * @param ReflectionClass<object>|ReflectionMethod $reflection
     * @param class-string<T> $attributeClass
     *
     * @return array<int, T>
     */
    private static function attributeInstances(ReflectionClass|ReflectionMethod $reflection, string $attributeClass): array
    {
        $instances = [];

        foreach ($reflection->getAttributes($attributeClass) as $attribute) {
            $instances[] = $attribute->newInstance();
        }

        /** @var array<int, T> $instances */
        return $instances;
    }

    /**
     * @param array<string, mixed> $permissions
     * @param array<int, string> $keys
     * @param class-string $controllerClass
     * @param array<int, string> $actions
     */
    private static function addControllerAccess(
        array &$permissions,
        string $section,
        array $keys,
        string $controllerClass,
        array $actions
    ): void {
        foreach ($keys as $key) {
            $current = $permissions[$section][$key]['controllers'][$controllerClass] ?? [];
            $permissions[$section][$key]['controllers'][$controllerClass] = self::mergeList((array)$current, $actions);
        }
    }

    /**
     * @param array<string, mixed> $permissions
     * @param array<int, string> $keys
     * @param class-string $controllerClass
     * @param array<int, string> $actions
     * @param array<int, string> $behaviors
     */
    private static function addActionBehaviors(
        array &$permissions,
        string $section,
        array $keys,
        string $controllerClass,
        array $actions,
        array $behaviors
    ): void {
        foreach ($keys as $key) {
            foreach ($actions as $action) {
                $current = $permissions[$section][$key]['behaviorActions'][$controllerClass][$action] ?? [];
                $permissions[$section][$key]['behaviorActions'][$controllerClass][$action] = self::mergeList(
                    (array)$current,
                    $behaviors
                );
            }
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function mergeValue(mixed $base, mixed $incoming): array
    {
        if (!is_array($base)) {
            $base = $base === null ? [] : [$base];
        }

        if (!is_array($incoming)) {
            $incoming = $incoming === null ? [] : [$incoming];
        }

        if (array_is_list($base) && array_is_list($incoming)) {
            return self::mergeList($base, $incoming);
        }

        foreach ($incoming as $key => $value) {
            if (array_key_exists($key, $base)) {
                $base[$key] = is_array($base[$key]) || is_array($value)
                    ? self::mergeValue($base[$key], $value)
                    : $value;
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * @param array<int, mixed> $base
     * @param array<int, mixed> $incoming
     *
     * @return array<int, mixed>
     */
    private static function mergeList(array $base, array $incoming): array
    {
        $list = [];

        foreach ([...$base, ...$incoming] as $value) {
            if ($value === null || in_array($value, $list, true)) {
                continue;
            }

            $list[] = $value;
        }

        if (in_array('*', $list, true)) {
            return ['*'];
        }

        return $list;
    }
}
