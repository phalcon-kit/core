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

use Attribute;

/**
 * Attaches controller behaviors through role or feature permissions.
 *
 * Behaviors declared with this attribute are compiled into action-scoped
 * permission metadata. When neither `roles` nor `features` is provided the
 * behavior is attached for the `everyone` role, which matches PhalconKit's
 * context role available to every identity.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class AttachBehavior
{
    /**
     * @var array<int, string>
     */
    public readonly array $behaviors;

    /**
     * @var array<int, string>
     */
    public readonly array $roles;

    /**
     * @var array<int, string>
     */
    public readonly array $features;

    /**
     * @var array<int, string>|null
     */
    public readonly ?array $actions;

    /**
     * @param array<int, string>|string $behaviors Behavior class or class list
     *     to attach.
     * @param array<int, string>|string|null $roles Optional direct ACL roles.
     * @param array<int, string>|string|null $features Optional permission
     *     features. Roles that already reference these features receive the
     *     behavior.
     * @param array<int, string>|string|null $actions Optional action names. Both
     *     `saveUser` and `save-user` are accepted.
     */
    public function __construct(
        array|string $behaviors,
        array|string|null $roles = null,
        array|string|null $features = null,
        array|string|null $actions = null
    ) {
        $this->behaviors = self::list($behaviors);
        $this->roles = $roles === null ? [] : self::list($roles);
        $this->features = $features === null ? [] : self::list($features);
        $this->actions = $actions === null ? null : self::list($actions);
    }

    /**
     * @return array<int, string>
     */
    private static function list(array|string $values): array
    {
        $values = is_array($values) ? $values : [$values];
        $normalized = [];

        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value === '' || in_array($value, $normalized, true)) {
                continue;
            }

            $normalized[] = $value;
        }

        return $normalized;
    }
}
