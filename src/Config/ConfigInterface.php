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

namespace PhalconKit\Config;

/**
 * PhalconKit configuration contract.
 *
 * The interface extends Phalcon's native config contract with helpers used by
 * framework providers and bootstraps. Consumers should type against this
 * interface when they need `pathToArray()` in addition to native `get()`,
 * `path()`, `merge()`, and `toArray()` behavior.
 *
 * @see https://docs.phalcon.io/5.15/config/
 */
interface ConfigInterface extends \Phalcon\Config\ConfigInterface
{
    /**
     * Resolve a dot-path and normalize the result to a PHP array.
     *
     * Native Phalcon config paths can return scalars, arrays, config objects,
     * or the supplied default. This helper preserves null as "missing" while
     * converting config objects and scalar values into arrays for provider code
     * that expects normal PHP array options.
     *
     * @param string $path Path understood by Phalcon's native `path()` method.
     * @param array|null $defaultValue Default returned when the path is
     *     missing.
     * @param string|null $delimiter Optional path delimiter.
     *
     * @return array|null Normalized array value, or null when the path
     *     resolves to null.
     */
    public function pathToArray(string $path, ?array $defaultValue = null, ?string $delimiter = null): ?array;
}
