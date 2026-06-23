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

namespace PhalconKit\Translate\Adapter;

use JetBrains\PhpStorm\Deprecated;
use Phalcon\Translate\Adapter\AbstractAdapter;
use Phalcon\Translate\InterpolatorFactory;
use PhalconKit\Exception\RuntimeException;

/**
 * Translation adapter backed by a nested PHP array.
 *
 * Phalcon's NativeArray adapter supports flat translation maps. This adapter
 * adds delimiter-based nested lookup, while still preserving exact flat-key
 * lookup precedence. A flat key such as `button.save` wins over a nested path
 * `button => ['save' => ...]` when both exist.
 *
 * Missing keys return the original key by default. Set `triggerError` to true
 * when development/test environments should fail fast on missing translations.
 *
 * Usage example:
 * ```php
 * $interpolator = new InterpolatorFactory();
 * $options = [
 *     'content' => [
 *         'en' => [
 *             'welcome' => 'Welcome to our website!',
 *             'goodbye' => 'Goodbye!',
 *         ],
 *         'fr' => [
 *             'welcome' => 'Bienvenue sur notre site web!',
 *             'goodbye' => 'Au revoir!',
 *         ],
 *     ],
 *     'triggerError' => false,
 *     'delimiter' => '.',
 * ];
 *
 * $translator = new NestedNativeArray($interpolator, $options);
 *
 * // Check if translation exists
 * $translator->has('en.welcome'); // returns true
 *
 * // Get translated string
 * $translator->query('fr.goodbye'); // returns 'Au revoir!'
 *
 * // Get translated string with placeholders
 * $translator->query('en.welcome', ['name' => 'John']); // returns 'Welcome to our website, John!'
 * ```
 *
 * @implements \ArrayAccess<string, mixed>
 *
 * @see https://docs.phalcon.io/5.16/translate/
 */
class NestedNativeArray extends AbstractAdapter implements \ArrayAccess
{
    /**
     * Translation content indexed by flat keys or nested arrays.
     *
     * @var array<string, mixed>
     */
    private array $translate = [];
    
    /**
     * Delimiter used when resolving nested translation paths.
     *
     * @var non-empty-string
     */
    protected string $delimiter = '.';
    
    /**
     * Create a nested-array translation adapter.
     *
     * Supported options:
     * - `content`: translation content as flat keys, nested arrays, or both.
     * - `triggerError`: throw a RuntimeException when a key is missing.
     * - `delimiter`: non-empty delimiter used for nested lookup.
     *
     * @param InterpolatorFactory $interpolator Factory used by Phalcon for
     *     placeholder replacement.
     * @param array<string, mixed> $options Adapter options.
     */
    public function __construct(InterpolatorFactory $interpolator, array $options)
    {
        parent::__construct($interpolator, $options);
        $this->delimiter = $options['delimiter'] ?? $this->delimiter ?: '.';
        $this->triggerError = (bool)($options['triggerError'] ?? $this->triggerError);
        $this->translate = $options['content'] ?? $this->translate;
    }
    
    /**
     * Check whether a translation exists for the given key.
     *
     * @deprecated since Phalcon Kit 1.0, use {@see self::has()} instead
     *
     * @param string $index Translation key to check.
     *
     * @return bool True when the key exists.
     *
     * @see has()
     */
    #[Deprecated(
        reason: 'since Phalcon Kit 1.0, use has() instead',
        replacement: '%class%->has(%parametersList%)'
    )]
    public function exists(string $index): bool
    {
        return $this->has($index);
    }
    
    /**
     * Return true when a flat or nested translation key exists.
     *
     * Exact flat keys are checked first. If no exact key exists, the key is
     * split by the configured delimiter and resolved through nested arrays.
     *
     * @param string $index Translation key to check.
     *
     * @return bool True when the key resolves to a configured translation.
     */
    #[\Override]
    public function has(string $index): bool
    {
        $translate = $this->translate;
        
        if (isset($translate[$index])) {
            return (bool)$translate[$index];
        }
        
        foreach (explode($this->delimiter ?: '.', $index) as $nestedIndex) {
            if (is_array($translate) && array_key_exists($nestedIndex, $translate)) {
                $translate = $translate[$nestedIndex];
            }
            else {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Return the missing key fallback or throw when strict mode is enabled.
     * 
     * @param string $index Missing translation key.
     *
     * @return string Original key when `triggerError` is false.
     *
     * @throws RuntimeException When `triggerError` is true.
     */
    #[\Override]
    public function notFound(string $index): string
    {
        if ($this->triggerError) {
            throw new RuntimeException('Cannot find translation key: ' . $index);
        }
    
        return $index;
    }
    
    /**
     * Return a translated string for a flat or nested key.
     *
     * Exact flat keys are returned before nested lookup is attempted. Nested
     * values are resolved with the configured delimiter and then passed through
     * Phalcon's placeholder interpolator.
     *
     * @param string $translateKey Translation key to resolve.
     * @param array<string, mixed> $placeholders Placeholder values passed to the
     *     interpolator.
     *
     * @return string Translated string, or the missing-key fallback.
     *
     * @throws RuntimeException When the key is missing and `triggerError` is
     *     true.
     */
    #[\Override]
    public function query(string $translateKey, array $placeholders = []): string
    {
        $translate = $this->translate;
        
        if (isset($translate[$translateKey])) {
            return $translate[$translateKey];
        }
        
        foreach (explode($this->delimiter ?: '.', $translateKey) as $nestedIndex) {
            if (is_array($translate) && array_key_exists($nestedIndex, $translate)) {
                $translate = $translate[$nestedIndex];
            }
            else {
                return $this->notFound($translateKey);
            }
        }
        
        return $this->replacePlaceholders($translate, $placeholders);
    }
    
    /**
     * Return the raw translation content.
     *
     * @return array<string, mixed> Translation content exactly as configured.
     */
    public function toArray(): array
    {
        return $this->translate;
    }
}
