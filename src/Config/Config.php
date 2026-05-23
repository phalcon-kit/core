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

class Config extends \Phalcon\Config\Config implements ConfigInterface
{
    /**
     * Retrieves a value from a path and converts it to an array.
     *
     * @param string $path The path to retrieve the value from.
     * @param array|null $defaultValue The default value to return if the path does not exist.
     * @param string|null $delimiter The delimiter to use for splitting the path.
     * @return array|null The value from the path as an array, or null if the value does not exist.
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
     * Merges the current configuration object with another set of data.
     *
     * @param array|PhalconConfigInterface $toMerge The data to merge into the current configuration.
     *                        This can be an array or another configuration object that implements PhalconConfigInterface.
     * @param bool $append Whether to append the data during the merge or replace existing values.
     *                       Defaults to false, meaning values will be replaced during the merge.
     * @return PhalconConfigInterface Returns the updated configuration object after the merge operation.
     *                                The current instance is modified and returned.
     * @throws InvalidArgumentException If the $toMerge parameter is not of a
     *     valid data type.
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
     * Merges two arrays recursively, appending values from the target array into the source array.
     * Handles both associative and indexed arrays.
     *
     * @param array $source The source array to merge values into.
     * @param array $target The target array to merge values from.
     *
     * @return array The resulting array after the merge operation.
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
     * Get a modified DateTime.
     * Note: This is a helper to enhance strict typings and safely use DateTime within config
     *
     * @param string $modifier The modifier string to modify the DateTime.
     * @param DateTimeImmutable|null $dateTime Optional. The DateTime to modify. Defaults to current DateTime if not provided.
     *
     * @return DateTimeImmutable The modified DateTime object.
     * @throws \DateMalformedStringException If the modifier cannot be parsed.
     */
    public function getDateTime(string $modifier, ?DateTimeImmutable $dateTime = null): DateTimeImmutable
    {
        $dateTime ??= new DateTimeImmutable();
        return $dateTime->modify($modifier);
    }
}
