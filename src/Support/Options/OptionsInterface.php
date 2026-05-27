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

namespace PhalconKit\Support\Options;

/**
 * Contract for objects that expose mutable runtime options.
 *
 * Implementations distinguish between default options captured at
 * initialization time and the current mutable option set. This lets services
 * temporarily override options and later reset them without reconstructing the
 * object.
 */
interface OptionsInterface
{
    /**
     * Initialize the object with an optional default option set.
     *
     * @param array<string, mixed>|null $options Defaults to capture and apply.
     */
    public function __construct(?array $options = null);

    /**
     * Capture defaults, apply current options, and run object initialization.
     *
     * @param array<string, mixed>|null $options Defaults to capture and apply.
     */
    public function initializeOptions(?array $options = null): void;

    /**
     * Optional hook called after options are initialized.
     */
    public function initialize(): void;

    /**
     * Replace the current option set.
     *
     * Null-valued entries remain present in the raw option array but are read
     * as missing by {@see getOption()}.
     *
     * @param array<string, mixed> $options Options to apply.
     */
    public function setOptions(array $options): void;

    /**
     * Return the current option set.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * Store or replace one option value.
     *
     * Passing null stores the key in the raw option array, but public lookups
     * treat that key as missing.
     */
    public function setOption(string $key, mixed $value = null): void;

    /**
     * Return one option value or a default when it is missing or null.
     */
    public function getOption(string $key, mixed $default = null): mixed;

    /**
     * Remove one option key when it exists in the raw option array.
     */
    public function removeOption(string $key): void;

    /**
     * Restore current options to the initialized defaults.
     */
    public function resetOptions(): void;

    /**
     * Remove all current option values.
     */
    public function clearOptions(): void;
}
