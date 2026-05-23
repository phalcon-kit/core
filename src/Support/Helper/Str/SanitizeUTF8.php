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

namespace PhalconKit\Support\Helper\Str;

/**
 * Normalize input text to UTF-8 and remove invalid characters.
 */
class SanitizeUTF8
{
    /**
     * Detect common encodings, convert safely to UTF-8, and strip invalid text.
     *
     * @param string $invalidUtf8Regex Multibyte regex used to remove invalid
     *     characters after conversion.
     */
    public function __invoke(string $string, string $invalidUtf8Regex = '[^\x20-\x7E\xA0-\xFF]'): string
    {
        // Detect encoding; fallback to UTF-8
        $encoding = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8';
        
        // Convert safely to UTF-8; mb_convert_encoding can return false
        $string = mb_convert_encoding($string, 'UTF-8', $encoding) ?: '';
        
        // Remove invalid UTF-8 characters
        $sanitized = mb_ereg_replace($invalidUtf8Regex, '', $string);
        
        return $sanitized ?: '';
    }
}
