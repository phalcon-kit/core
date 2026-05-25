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
 * Normalize line-break sequences in text.
 *
 * The default pattern converts CRLF and old Mac CR line breaks to LF while
 * leaving existing LF characters untouched. Callers can pass a custom regex and
 * replacement when they need a different normalization policy.
 */
class NormalizeLineBreaks
{
    /**
     * Replace matching line-break sequences.
     *
     * @param string $string The input string where line breaks will be replaced.
     * @param string $lineBreaksRegex Regex passed to `preg_replace()`. An empty
     *     string disables replacement and returns the input unchanged.
     * @param string $replacement Replacement text for matched line breaks.
     *
     * @return string The processed string with line breaks replaced.
     */
    public function __invoke(string $string, string $lineBreaksRegex = "/\r\n|\r/", string $replacement = "\n"): string
    {
        return $lineBreaksRegex === '' ? $string : preg_replace($lineBreaksRegex, $replacement, $string) ?? '';
    }
}
