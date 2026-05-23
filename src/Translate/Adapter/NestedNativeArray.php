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
 * NestedNativeArray class is an implementation of the Translate Adapter interface that uses
 * a nested array as the translation source.
 *
 * Usage example:
 * ```
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
 * $translator->exists('en.welcome'); // returns true
 *
 * // Get translated string
 * $translator->query('fr.goodbye'); // returns 'Au revoir!'
 *
 * // Get translated string with placeholders
 * $translator->query('en.welcome', ['name' => 'John']); // returns 'Welcome to our website, John!'
 * ```
 *
 * @implements \ArrayAccess<string, mixed>
 * @package PhalconKit\Translate\Adapter
 */
class NestedNativeArray extends AbstractAdapter implements \ArrayAccess
{
    /**
     * Translation array.
     * @var array $translate
     */
    private array $translate = [];
    
    /**
     * @var bool $triggerError Determines whether to trigger an error or not.
     */
    private bool $triggerError = false;
    
    /**
     * Sets the value of the delimiter.
     *
     * @param non-empty-string $delimiter The delimiter value to set.
     */
    protected string $delimiter = '.';
    
    /**
     * PhalconKit\Translate\Adapter\NestedNativeArray constructor
     *
     * @param InterpolatorFactory $interpolator
     * @param array $options [
     *     'content' => '',
     *     'triggerError' => false,
     *     'delimiter' => '.',
     * ]
     */
    public function __construct(InterpolatorFactory $interpolator, array $options)
    {
        parent::__construct($interpolator, $options);
        $this->delimiter = $options['delimiter'] ?? $this->delimiter ?: '.';
        $this->triggerError = $options['triggerError'] ?? $this->triggerError;
        $this->translate = $options['content'] ?? $this->translate;
    }
    
    /**
     * Returns whether a translation exists for the given index
     *
     * @param string $index The translation index to check
     * @return bool True if a translation exists for the index, false otherwise
     * @deprecated since Phalcon Kit 1.0, use {@see self::has()} instead
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
     * Returns true if the translation for the given index exists, false otherwise.
     * @param string $index The index of the translation to check.
     * @return bool Returns true if the translation for the given index exists, false otherwise.
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
     * Returns the index if translation key is not found
     * Throws exception if triggerError is enabled and translation key is not found
     * 
     * @param string $index The translation key
     * @return string Returns the index
     * @throws RuntimeException Throws exception if triggerError is enabled and
     *     translation key is not found.
     */
    public function notFound(string $index): string
    {
        if ($this->triggerError) {
            throw new RuntimeException('Cannot find translation key: ' . $index);
        }
    
        return $index;
    }
    
    /**
     * Queries for a translated string based on the given translate key and optional placeholders.
     *
     * @param string $translateKey The translate key to query for a translated string.
     * @param array $placeholders An optional array of placeholders to replace in the translated string.
     * @return string The translated string or the not found string if the translate key is not found.
     * @throws RuntimeException Throws exception if triggerError is enabled and
     *     translation key is not found.
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
     * Converts the translate data to an array.
     *
     * @return array The translate data as an array.
     */
    public function toArray(): array
    {
        return $this->translate;
    }
}
