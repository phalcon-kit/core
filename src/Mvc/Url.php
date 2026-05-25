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

namespace PhalconKit\Mvc;

/**
 * URL service that normalizes generated local paths.
 *
 * PhalconKit keeps Phalcon's URL generation behavior, then normalizes local
 * paths to absolute paths by resolving duplicate separators and `.`/`..`
 * segments. Fully-qualified and protocol-relative URLs are preserved.
 */
class Url extends \Phalcon\Mvc\Url
{
    /**
     * Generate a URL and normalize local results to absolute paths.
     *
     * @param array|string|null $uri Phalcon route name/path input.
     * @param mixed $args Route or query arguments passed to Phalcon.
     * @param bool|null $local Whether Phalcon should treat the URL as local.
     * @param mixed $baseUri Optional base URI override.
     * @param bool $replaceArgs Whether route placeholders should be replaced.
     *
     * @return string Generated URL with local paths normalized.
     */
    #[\Override]
    public function get($uri = null, $args = null, ?bool $local = null, $baseUri = null, bool $replaceArgs = false): string
    {
        return self::getAbsolutePath(parent::get($uri, $args, $local, $baseUri, $replaceArgs));
    }
    
    /**
     * Normalize a local path into an absolute path.
     *
     * Absolute HTTP(S) URLs and protocol-relative URLs are returned unchanged
     * because normalizing their path component here could alter an external
     * target. Local paths are normalized by converting backslashes to forward
     * slashes, removing empty and `.` segments, and resolving `..` segments
     * without allowing the result to escape above `/`.
     *
     * @param string $path Local path or fully-qualified URL.
     *
     * @return string Normalized absolute local path, or the original external
     *     URL.
     */
    public static function getAbsolutePath(string $path): string
    {
        if (str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'http://')) {
            return $path;
        }
        if (str_starts_with($path, '//')) {
            return $path;
        }
        
        $path = str_replace(['/', '\\'], '/', $path);
        $parts = array_filter(explode('/', $path), function (mixed $string) {
            return !empty($string);
        });
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' === $part) {
                continue;
            }
            if ('..' === $part) {
                array_pop($absolutes);
            }
            else {
                $absolutes[] = $part;
            }
        }
        return '/' . implode('/', $absolutes);
    }
}
