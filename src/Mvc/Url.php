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
 * {@inheritDoc}
 */
class Url extends \Phalcon\Mvc\Url
{
    /**
     * {@inheritdoc}
     *
     * @param array|string|null $uri
     * @param mixed $args
     * @param bool|null $local
     * @param mixed $baseUri
     *
     * @return string
     */
    #[\Override]
    public function get($uri = null, $args = null, ?bool $local = null, $baseUri = null): string
    {
        return self::getAbsolutePath(parent::get($uri, $args, $local, $baseUri));
    }
    
    /**
     * Returns the absolute path from the given path.
     *
     * @param string $path The path to convert to an absolute path.
     *
     * @return string The absolute path.
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
