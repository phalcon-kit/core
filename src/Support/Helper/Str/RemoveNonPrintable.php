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
 * Remove non-printable characters from text.
 *
 * The helper uses multibyte regular expressions so callers can customize the
 * character class without losing UTF-8 behavior. By default it strips control
 * characters and line endings.
 */
class RemoveNonPrintable
{
    /**
     * Replace characters matching the non-printable regex.
     *
     * @param string $string Input text.
     * @param string $nonPrintableRegex Multibyte regex passed to
     *     `mb_ereg_replace()`.
     * @param string $replacement Replacement text for matched characters.
     *
     * @return string Sanitized text, or an empty string when replacement fails.
     */
    public function __invoke(string $string, string $nonPrintableRegex = '[[:cntrl:]' . PHP_EOL . ']', string $replacement = ''): string
    {
        return mb_ereg_replace($nonPrintableRegex, $replacement, $string) ?: '';
    }
}
