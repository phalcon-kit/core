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

use PhalconKit\Exception\ServiceException;
use Transliterator;

class Slug
{
    /**
     * Generates a cleaned and formatted string by performing transliteration, replacements, and normalization.
     * - Transliterates characters to Latin equivalents.
     * - Replaces specified substrings in the input string.
     * - Cleans the string and normalizes it using a specified delimiter.
     * - Creates a slug to be used for pretty URLs
     *
     * @param string $string The input string to be transformed.
     * @param array $replace An associative array of substrings to replace, where keys are substrings to find and values are their replacements.
     * @param string $delimiter The character to use as a replacement for unwanted characters in the string.
     * @return string The transformed, cleaned, and formatted string.
     * @throws ServiceException When the PHP intl transliterator cannot be
     *     created or transliteration fails.
     */
    public static function generate(string $string, array $replace = [], string $delimiter = '-'): string
    {
        // Save the old locale and set the new locale to UTF-8
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');

        try {
            if (!empty($replace)) {
                $string = str_replace(array_keys($replace), array_values($replace), $string);
            }

            $transliterator = self::createTransliterator('Any-Latin; Latin-ASCII');
            $string = $transliterator->transliterate(
                (string)mb_convert_encoding(htmlspecialchars_decode($string), 'UTF-8', 'auto')
            );

            if (!is_string($string)) {
                throw new ServiceException('Slug transliteration failed.');
            }

            return self::cleanString($string, $delimiter);
        }
        finally {
            self::restoreLocale($oldLocale ?: '');
        }
    }

    /**
     * Creates the ICU transliterator used by slug generation.
     *
     * Keeping this in one place gives callers a stable framework exception when
     * the intl extension is missing or ICU rejects the transliterator rule set.
     *
     * @throws ServiceException When the transliterator class or identifier is
     *     not available.
     */
    private static function createTransliterator(string $identifier): Transliterator
    {
        if (!class_exists(Transliterator::class)) {
            throw new ServiceException('Slug generation requires the PHP intl Transliterator class.');
        }

        $transliterator = Transliterator::create($identifier);
        if (!$transliterator instanceof Transliterator) {
            throw new ServiceException(sprintf(
                'Could not create slug transliterator "%s".',
                $identifier
            ));
        }

        return $transliterator;
    }
    
    /**
     * Restore the locale settings based on the provided old locale.
     *
     * @param string|string[] $oldLocale The old locale settings.
     *                                   Can be either a string or an array of locale settings.
     *                                   If a string, it will be parsed and converted to an array of locale settings.
     * @return void
     */
    private static function restoreLocale(string|array $oldLocale): void
    {
        if (is_string($oldLocale)) {
            if ($oldLocale === '' || setlocale(LC_ALL, $oldLocale) !== false) {
                return;
            }

            parse_str(str_replace(';', '&', $oldLocale), $locales);
            $oldLocale = array_values($locales);
        }
        setlocale(LC_ALL, $oldLocale);
    }
    
    /**
     * Cleans a given string by normalizing it to a specific format and replacing unwanted characters with a specified delimiter.
     * - Replace non-letter or non-digits by "-"
     * - Trim trailing "-"
     *
     * @param string $string The input string to be cleaned.
     * @param string $delimiter The character to use as a replacement for unwanted characters in the string.
     * @return string The cleaned and formatted string.
     */
    public static function cleanString(string $string, string $delimiter): string
    {
        if ($string === '') {
            return '';
        }
        
        $string = preg_replace('#[^\pL\d]+#u', '-', $string) ?? '';
        if ($string === '') {
            return '';
        }
        
        $string = trim($string, '-');
        $string = strtolower(preg_replace('~[^-\w]+~', '', $string) ?? '');
        $string = preg_replace('#[/_|+ -]+#', $delimiter, $string) ?? '';
        
        return trim($string, $delimiter);
    }
}
