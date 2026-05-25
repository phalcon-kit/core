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

namespace PhalconKit\Support;

/**
 * Miscellaneous low-level utility helpers.
 *
 * These methods are intentionally small and static because they are used by
 * bootstrap, diagnostics, and legacy integration code before richer services
 * are always available. Prefer more specific services/helpers for new domain
 * behavior.
 */
class Utils
{
    /**
     * Remove memory and execution-time limits for long-running maintenance work.
     *
     * This changes process-wide PHP INI settings. It is appropriate for trusted
     * CLI maintenance tasks, but should be used carefully in request/worker
     * contexts where unlimited runtime can exhaust server resources.
     */
    public static function setUnlimitedRuntime(): void
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', 0);
        set_time_limit(0);
    }
    
    /**
     * Return the namespace of an object instance.
     *
     * @throws \ReflectionException If the object cannot be reflected.
     */
    public static function getNamespace(object $class): string
    {
        return (new \ReflectionClass($class))->getNamespaceName();
    }
    
    /**
     * Return the short class name of an object instance.
     *
     * @throws \ReflectionException If the object cannot be reflected.
     */
    public static function getShortName(object $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }
    
    /**
     * Return the fully qualified class name of an object instance.
     *
     * @throws \ReflectionException If the object cannot be reflected.
     */
    public static function getName(object $class): string
    {
        return (new \ReflectionClass($class))->getName();
    }
    
    /**
     * Return the directory containing an object's declaring file.
     *
     * @throws \ReflectionException If the object cannot be reflected.
     */
    public static function getDirname(object $class): string
    {
        return dirname((new \ReflectionClass($class))->getFileName());
    }
    
    /**
     * Return current and peak memory usage.
     *
     * @param float $divider Number used to convert bytes into display units.
     * @param string $suffix Suffix appended to formatted values.
     *
     * @return array{
     *      memory: string,
     *      memoryPeak: string,
     *      realMemory: string,
     *      realMemoryPeak: string,
     *  }
     */
    public static function getMemoryUsage(float $divider = 1048576.2, string $suffix = ' MB'): array
    {
        return [
            'memory' => number_format((float)memory_get_usage() / $divider, 2) . $suffix,
            'memoryPeak' => number_format((float)memory_get_peak_usage() / $divider, 2) . $suffix,
            'realMemory' => number_format((float)memory_get_usage(true) / $divider, 2) . $suffix,
            'realMemoryPeak' => number_format((float)memory_get_peak_usage(true) / $divider, 2) . $suffix,
        ];
    }
}
