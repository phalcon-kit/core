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
 * Declares controller actions that belong to one or more permission features.
 *
 * Roles still opt into features through normal permission config. The attribute
 * only contributes the controller/action component entries, keeping feature
 * assignment central while allowing resource classes to declare their own
 * stable action surface.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class PermissionFeature
{
    /**
     * @var array<int, string>
     */
    public readonly array $features;

    /**
     * @var array<int, string>|null
     */
    public readonly ?array $actions;

    /**
     * @param array<int, string>|string $features Permission feature names.
     * @param array<int, string>|string|null $actions Optional action names. Both
     *     `findFirstWith` and `find-first-with` are accepted and normalized.
     */
    public function __construct(array|string $features, array|string|null $actions = null)
    {
        $this->features = self::list($features);
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
