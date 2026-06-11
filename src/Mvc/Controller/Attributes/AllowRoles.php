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
 * Grants controller actions directly to one or more ACL roles.
 *
 * The attribute is additive. It compiles into the same permission array consumed
 * by the existing ACL service, so config-driven features and roles continue to
 * work. Method-level attributes default to the annotated `*Action()` method;
 * class-level attributes default to `*` unless `actions` is provided.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class AllowRoles
{
    /**
     * @var array<int, string>
     */
    public readonly array $roles;

    /**
     * @var array<int, string>|null
     */
    public readonly ?array $actions;

    /**
     * @param array<int, string>|string $roles ACL roles to grant.
     * @param array<int, string>|string|null $actions Optional action names. Both
     *     `findWith` and `find-with` are accepted and normalized.
     */
    public function __construct(array|string $roles, array|string|null $actions = null)
    {
        $this->roles = self::list($roles);
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
