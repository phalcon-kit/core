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

namespace PhalconKit\Support\Helper\Str;

use PhalconKit\Support\Slug;

/**
 * Create URL-friendly slugs through the shared slug generator.
 *
 * This helper exposes {@see Slug::generate()} through the helper factory and
 * static helper facade, so application code can call `Helper::slugify()` or
 * resolve the helper service directly.
 */
class Slugify
{
    /**
     * Generate a normalized slug.
     *
     * @param string $string Source text.
     * @param array<string, string> $replace Search/replace pairs applied before
     *     slug cleanup.
     * @param string $delimiter Word delimiter used in the final slug.
     */
    public function __invoke(string $string, array $replace = [], string $delimiter = '-'): string
    {
        return Slug::generate($string, $replace, $delimiter);
    }
}
