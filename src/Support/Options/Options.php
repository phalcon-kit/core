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
 * Reusable implementation for mutable service/object options.
 *
 * Classes using the trait get a simple lifecycle: constructor options are
 * captured as defaults, current options can be replaced or changed by key, and
 * `resetOptions()` restores the captured defaults. Override `initialize()` for
 * post-option setup that should run once during construction.
 */
trait Options
{
    /**
     * Options captured during initialization and used by resetOptions().
     *
     * @var array<string, mixed>
     */
    protected array $defaultOptions = [];

    /**
     * Current mutable option values.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Construct the object and initialize its options.
     *
     * @param array<string, mixed>|null $options Defaults to capture and apply.
     */
    public function __construct(?array $options = null)
    {
        $this->initializeOptions($options);
    }
    
    /**
     * Capture defaults, apply the current options, and run initialize().
     *
     * @param array<string, mixed>|null $options Defaults to capture and apply.
     */
    public function initializeOptions(?array $options = null): void
    {
        $options ??= [];
        $this->defaultOptions = $options;
        $this->setOptions($options);
        $this->initialize();
    }
    
    /**
     * Optional hook called after options are initialized.
     *
     * Override this in classes that need to derive internal state from options
     * during construction.
     */
    public function initialize(): void
    {
    }
    
    /**
     * Replace or merge the current option set.
     *
     * @param array<string, mixed> $options Options to apply.
     * @param bool $merge Whether to merge into existing options instead of
     *     replacing them.
     */
    public function setOptions(array $options, bool $merge = false): void
    {
        $this->options = $merge ? array_merge($this->options, $options) : $options;
    }
    
    /**
     * Return the current option set.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
    
    /**
     * Store or replace one option value.
     *
     * @param bool $merge Whether to merge the key/value pair into the existing
     *     option array.
     */
    public function setOption(string $key, mixed $value = null, bool $merge = false): void
    {
        if ($merge) {
            $this->options = array_merge($this->options, [$key => $value]);
        } else {
            $this->options[$key] = $value;
        }
    }
    
    /**
     * Return one option value or a default when it is missing.
     *
     * @param mixed $default Default returned when the option is not set.
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
    
    /**
     * Return true when an option is present.
     */
    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }
    
    /**
     * Remove one option value when it exists.
     */
    public function removeOption(string $key): void
    {
        if (isset($this->options[$key])) {
            unset($this->options[$key]);
        }
    }
    
    /**
     * Restore current options to the initialized defaults.
     */
    public function resetOptions(): void
    {
        $this->setOptions($this->defaultOptions);
    }
    
    /**
     * Remove all current option values.
     */
    public function clearOptions(): void
    {
        $this->options = [];
    }
}
