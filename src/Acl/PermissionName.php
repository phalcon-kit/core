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

namespace PhalconKit\Acl;

/**
 * Normalizes controller and action names used by ACL permissions.
 *
 * Public routes often use dash-case while PHP actions use camelCase methods.
 * This helper gives permission config, attributes, dispatcher security, and
 * behavior attachment one shared vocabulary: component checks prefer the real
 * handler class and action checks canonicalize to dash-case while retaining raw
 * aliases for backwards compatibility.
 */
final class PermissionName
{
    /**
     * Normalize a dispatcher or method action name into the ACL action key.
     *
     * Examples:
     *
     * - `findWith` becomes `find-with`
     * - `find-with` remains `find-with`
     * - `findWithAction` becomes `find-with`
     *
     * @param string $action Action name from a route, dispatcher, method, or
     *     permission config.
     *
     * @return string Dash-case ACL action key, `*`, or an empty string.
     */
    public static function action(string $action): string
    {
        $action = trim($action);
        if ($action === '' || $action === '*') {
            return $action;
        }

        if (str_ends_with($action, 'Action')) {
            $action = substr($action, 0, -6);
        }

        return self::dash($action);
    }

    /**
     * Return action aliases to try for a dispatcher action.
     *
     * The canonical dash-case action is first so new config and attributes take
     * precedence. The raw action follows so older camelCase permission configs
     * remain valid during migration.
     *
     * @param string $action Dispatcher action name.
     *
     * @return array<int, string> Unique action candidates.
     */
    public static function actionCandidates(string $action): array
    {
        return self::unique([self::action($action), $action]);
    }

    /**
     * Normalize a public action list for native Phalcon ACL registration.
     *
     * Raw camelCase entries are preserved and dash-case aliases are added. This
     * keeps direct `Acl::isAllowed(..., 'findWith')` callers compatible while
     * allowing dispatcher security to check canonical dash-case actions.
     *
     * @param mixed $accessList String, array, or scalar access list.
     *
     * @return array<int, string> ACL access names.
     */
    public static function accessList(mixed $accessList): array
    {
        $accesses = is_array($accessList) ? $accessList : [$accessList];
        $normalized = [];

        foreach ($accesses as $access) {
            if ($access === null) {
                continue;
            }

            $access = trim((string)$access);
            if ($access === '') {
                continue;
            }

            if ($access === '*') {
                return ['*'];
            }

            $normalized[] = self::action($access);
            $normalized[] = $access;
        }

        return self::unique($normalized);
    }

    /**
     * Derive a canonical action key from an action method name.
     *
     * @param string $methodName PHP method name, usually ending in `Action`.
     *
     * @return string Dash-case ACL action key.
     */
    public static function actionFromMethod(string $methodName): string
    {
        return self::action($methodName);
    }

    /**
     * Build component aliases for the active controller or task.
     *
     * The fully qualified handler class remains the preferred component key.
     * Short class names and route-style aliases are accepted so existing apps can
     * gradually move from route names such as `project-user` to class constants.
     *
     * @param string $handlerClass Fully qualified dispatcher handler class.
     * @param string|null $routeName Controller or task name from the dispatcher.
     * @param string $suffix Handler suffix, usually `Controller` or `Task`.
     *
     * @return array<int, string> Unique component candidates.
     */
    public static function handlerCandidates(string $handlerClass, ?string $routeName = null, string $suffix = 'Controller'): array
    {
        $shortClass = self::shortClass($handlerClass);
        $baseClass = self::withoutSuffix($shortClass, $suffix);
        $candidates = [$handlerClass, $shortClass, $baseClass, self::dash($baseClass)];

        if ($routeName !== null && $routeName !== '') {
            $candidates[] = $routeName;
            $candidates[] = self::dash($routeName);
            $routeBase = self::withoutSuffix($routeName, $suffix);
            $candidates[] = $routeBase;
            $candidates[] = self::dash($routeBase);
        }

        return self::unique($candidates);
    }

    /**
     * Normalize a method/class attribute action list.
     *
     * @param array<int, string>|null $actions Explicit attribute actions.
     * @param array<int, string> $defaultActions Actions used when the attribute
     *     omitted its `actions` argument.
     *
     * @return array<int, string> Canonical dash-case action keys.
     */
    public static function normalizeAttributeActions(?array $actions, array $defaultActions): array
    {
        $actions ??= $defaultActions;
        $normalized = [];

        foreach ($actions as $action) {
            $action = self::action($action);
            if ($action === '') {
                continue;
            }

            if ($action === '*') {
                return ['*'];
            }

            $normalized[] = $action;
        }

        return self::unique($normalized);
    }

    /**
     * Convert camelCase, PascalCase, snake_case, or spaced names to dash-case.
     */
    private static function dash(string $name): string
    {
        $name = trim($name);
        if ($name === '' || $name === '*') {
            return $name;
        }

        $name = str_replace('\\', '/', $name);
        $name = basename($name);
        $name = str_replace(['_', ' '], '-', $name);
        $name = (string)preg_replace('/(?<!^)[A-Z]/', '-$0', $name);
        $name = (string)preg_replace('/-+/', '-', $name);

        return strtolower(trim($name, '-'));
    }

    /**
     * @return array<int, string>
     */
    private static function unique(array $values): array
    {
        $unique = [];

        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value === '' || in_array($value, $unique, true)) {
                continue;
            }

            $unique[] = $value;
        }

        return $unique;
    }

    private static function shortClass(string $className): string
    {
        $className = trim($className, '\\');
        $position = strrpos($className, '\\');

        return $position === false ? $className : substr($className, $position + 1);
    }

    private static function withoutSuffix(string $name, string $suffix): string
    {
        if ($suffix !== '' && str_ends_with($name, $suffix)) {
            return substr($name, 0, -strlen($suffix));
        }

        return $name;
    }
}
