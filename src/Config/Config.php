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

use Phalcon\Config\ConfigInterface as PhalconConfigInterface;
use DateTimeImmutable;
use PhalconKit\Exception\InvalidArgumentException;

/**
 * PhalconKit config wrapper with framework merge and typed-path helpers.
 *
 * The class keeps native `Phalcon\Config\Config` behavior and adds:
 *
 * - `pathToArray()` for provider code that needs array options.
 * - append-aware recursive merge support for config fragments.
 * - `getDateTime()` for lifecycle/retention config that stores date modifiers.
 *
 * @see https://docs.phalcon.io/5.13/config/
 */
class Config extends \Phalcon\Config\Config implements ConfigInterface
{
    /**
     * Resolve a config path and normalize the result to an array.
     *
     * `null` is preserved so callers can distinguish a missing optional path
     * from a configured scalar value. Native Phalcon config objects are
     * converted through `toArray()`, and any other non-null value is cast to an
     * array.
     *
     * @param string $path Path understood by Phalcon's native `path()` method.
     * @param array|null $defaultValue Default returned when the path is
     *     missing.
     * @param string|null $delimiter Optional path delimiter.
     *
     * @return array|null Normalized array value, or null when the path
     *     resolves to null.
     */
    #[\Override]
    public function pathToArray(string $path, ?array $defaultValue = null, ?string $delimiter = null): ?array
    {
        $ret = $this->path($path, $defaultValue, $delimiter);
        
        if (is_null($ret)) {
            return null;
        }
        
        if ($ret instanceof PhalconConfigInterface) {
            return $ret->toArray();
        }
        
        return (array)$ret;
    }
    
    /**
     * Merge data into this config instance.
     *
     * When `$append` is false, native Phalcon merge behavior is used. When
     * `$append` is true, numeric-keyed values are appended while associative
     * values are replaced recursively. This is useful for framework config
     * fragments such as provider lists, permission features, and default seed
     * data where applications need to extend list values instead of replacing
     * the whole list.
     *
     * @param array|PhalconConfigInterface $toMerge Data to merge into this
     *     config.
     * @param bool $append Use PhalconKit append-aware merge semantics.
     *
     * @return PhalconConfigInterface The current mutated config instance.
     *
     * @throws InvalidArgumentException When append mode receives a value that
     *     cannot be converted to an array.
     */
    #[\Override]
    public function merge(mixed $toMerge, bool $append = false): PhalconConfigInterface
    {
        if (!$append) {
            return parent::merge($toMerge);
        }
        
        $source = $this->toArray();
        $this->clear();
        $toMerge = $toMerge instanceof PhalconConfigInterface ? $toMerge->toArray() : $toMerge;
        
        if (!is_array($toMerge)) {
            throw new InvalidArgumentException('Invalid data type for merge.');
        }
        
        $result = $this->internalMergeAppend($source, $toMerge);
        $this->init($result);
        return $this;
    }
    
    /**
     * Append-merge two arrays recursively.
     *
     * Integer keys are appended to preserve list-style config fragments.
     * String keys replace existing values unless both sides contain arrays, in
     * which case the merge recurses.
     *
     * @param array $source Base config data.
     * @param array $target Incoming config data.
     *
     * @return array Merged config data.
     */
    final protected function internalMergeAppend(array $source, array $target): array
    {
        foreach ($target as $key => $value) {
            if (is_array($value) && isset($source[$key]) && is_array($source[$key])) {
                $source[$key] = $this->internalMergeAppend($source[$key], $value);
            }
            elseif (is_int($key)) {
                $source[] = $value;
            }
            else {
                $source[$key] = $value;
            }
        }
        
        return $source;
    }
    
    /**
     * Return a modified immutable date.
     *
     * This helper keeps date-modifier config strongly typed in lifecycle and
     * retention code. When no base date is provided, the current time is used.
     *
     * @param string $modifier Date/time modifier accepted by
     *     `DateTimeImmutable::modify()`, such as `-1 month` or `+7 days`.
     * @param DateTimeImmutable|null $dateTime Optional base date.
     *
     * @return DateTimeImmutable Modified date.
     *
     * @throws \DateMalformedStringException If the modifier cannot be parsed.
     */
    public function getDateTime(string $modifier, ?DateTimeImmutable $dateTime = null): DateTimeImmutable
    {
        $dateTime ??= new DateTimeImmutable();
        return $dateTime->modify($modifier);
    }
}
