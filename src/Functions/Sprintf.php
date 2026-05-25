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

if (!function_exists('implode_sprintf')) {
    /**
     * Format every non-null array value and join the formatted parts.
     *
     * The callback receives both value and key, so formats can use `%1$s` for
     * the value and `%2$s` for the key. Null values are omitted before
     * formatting; false, zero, and empty strings are preserved.
     *
     * @param array<array-key, mixed> $array Values to format.
     * @param string $glue String inserted between formatted values.
     * @param string $format `sprintf()`/`mb_vsprintf()` format string.
     * @param bool $multibyte Whether formatting should use `mb_vsprintf()`.
     * @param string|null $encoding Encoding used when multibyte formatting is
     *     enabled. Null uses `mb_internal_encoding()`.
     *
     * @return string Joined formatted values, or an empty string for an empty
     *     input array.
     */
    function implode_sprintf(
        array $array = [],
        string $glue = ' ',
        string $format = '%s',
        bool $multibyte = false,
        ?string $encoding = null
    ): string {
        $array = array_filter($array, static fn (mixed $value): bool => $value !== null);
        
        return implode($glue, array_map(static function (mixed $value, string|int $key) use ($format, $multibyte, $encoding): string {
            return $multibyte
                ? mb_vsprintf($format, [$value, $key], $encoding)
                : sprintf($format, $value, $key);
        }, $array, array_keys($array)));
    }
}

if (!function_exists('implode_mb_sprintf')) {
    /**
     * Multibyte-safe variant of `implode_sprintf()`.
     *
     * Values are formatted with `mb_vsprintf()` so string width and precision
     * handling respect the selected encoding.
     *
     * @param array<array-key, mixed> $array Values to format.
     * @param string $glue String inserted between formatted values.
     * @param string $format Multibyte sprintf format string.
     * @param string|null $encoding Encoding used for multibyte formatting. Null
     *     uses `mb_internal_encoding()`.
     *
     * @return string Joined formatted values.
     */
    function implode_mb_sprintf(array $array = [], string $glue = ' ', string $format = '%s', ?string $encoding = null): string
    {
        return implode_sprintf($array, $glue, $format, true, $encoding);
    }
}

if (!function_exists('sprintfn')) {
    /**
     * Format a string with named placeholders backed by `vsprintf()`.
     *
     * Named placeholders use PHP's positional syntax with a symbolic name in
     * place of the numeric position. The names are rewritten to numeric
     * positions before calling `vsprintf()`.
     *
     * Example:
     * ```php
     * sprintfn('second: %second$s ; first: %first$s', [
     *     'first' => '1st',
     *     'second' => '2nd',
     * ]);
     * ```
     *
     * @param string $format Sprintf format string containing named placeholders.
     * @param array<string, mixed> $args Replacement values keyed by placeholder
     *     name.
     *
     * @return string|false Formatted string, or false after emitting a warning
     *     when a named placeholder has no matching argument.
     */
    function sprintfn(string $format, array $args = []): false|string
    {
        // map of argument names to their corresponding sprintf numeric argument value
        $array = array_keys($args);
        array_unshift($array, 0);
        $array = array_flip(array_slice($array, 1, null, true));
        
        // find the next named argument. each search starts at the end of the previous replacement.
        for ($pos = 0; preg_match('/(?<=%)([a-zA-Z_]\w*)(?=\$)/', $format, $match, PREG_OFFSET_CAPTURE, $pos);) {
            $position = intval($match[0][1]);
            $length = strlen($match[0][0]);
            $key = $match[1][0];
            
            // programmer did not supply a value for the named argument found in the format string
            if (!array_key_exists($key, $array)) {
                user_error("sprintfn(): Missing argument '{${$key}}'", E_USER_WARNING);
                return false;
            }
            
            // replace the named argument with the corresponding numeric one
            $replace = (string)$array[$key];
            $format = substr_replace($format, $replace, $position, $length);
            $pos = $position + strlen($replace);
            
            // skip to end of replacement for next iteration
        }
        
        return vsprintf($format, array_values($args));
    }
}

if (!function_exists('mb_sprintf')) {
    /**
     * Return a formatted multibyte string.
     *
     * This convenience wrapper delegates to `mb_vsprintf()`. It is intended for
     * ASCII-preserving encodings such as UTF-8 and ISO-8859 variants, and it
     * handles sign, padding, alignment, width, and precision. Argument swapping
     * is intentionally not supported by the multibyte implementation.
     *
     * @param string $format Multibyte-aware sprintf format.
     * @param string|int|float ...$args Format arguments.
     *
     * @return string Formatted string.
     */
    function mb_sprintf(string $format, string|int|float ...$args): string
    {
        return mb_vsprintf($format, $args);
    }
}

if (!function_exists('mb_vsprintf')) {
    /**
     * Return a formatted string with multibyte-aware `%s` handling.
     *
     * The format is converted to UTF-8 while parsing directives, then converted
     * back to the requested encoding before delegating non-string directives to
     * `vsprintf()`. String directives support sign, padding, alignment, width,
     * and precision. Argument swapping is intentionally not supported.
     *
     * @param string $format Multibyte-aware sprintf format.
     * @param array<int, mixed> $argv Format arguments.
     * @param string|null $encoding Encoding used for the format and arguments.
     *     Null uses `mb_internal_encoding()`.
     *
     * @return string Formatted string.
     *
     * @author Viktor Söderqvist <viktor@textalk.se>
     *
     * @link http://php.net/manual/en/function.sprintf.php#89020
     */
    function mb_vsprintf(string $format, array $argv, ?string $encoding = null): string
    {
        if (is_null($encoding)) {
            $encoding = strval(mb_internal_encoding());
        }
        
        // Use UTF-8 in the format so we can use the u flag in preg_split
        $format = strval(mb_convert_encoding($format, 'UTF-8', $encoding));
        
        $newFormat = ''; // build a new format in UTF-8
        $newArgv = []; // unhandled args in unchanged encoding
        
        while ($format !== '') {
            // Split the format in two parts: $pre and $post by the first %-directive
            // We get also the matched groups
            $pregSplitResult =
                preg_split(
                    "!%(\+?)('.|[0 ]|)(-?)([1-9][0-9]*|)(\.[1-9][0-9]*|)([%a-zA-Z])!u",
                    $format,
                    2,
                    PREG_SPLIT_DELIM_CAPTURE
                );
            
            $pre = $pregSplitResult[0] ?? '';
            $sign = $pregSplitResult[1] ?? '';
            $filler = $pregSplitResult[2] ?? '';
            $align = $pregSplitResult[3] ?? '';
            $size = $pregSplitResult[4] ?? '';
            $precision = $pregSplitResult[5] ?? '';
            $type = $pregSplitResult[6] ?? '';
            $post = $pregSplitResult[7] ?? '';
            
            $newFormat .= mb_convert_encoding($pre, $encoding, 'UTF-8') ?: '';
            
            if ($type == '') {
                // didn't match. do nothing. this is the last iteration.
            }
            else if ($type == '%') {
                // an escaped %
                $newFormat .= '%%';
            }
            else if ($type == 's') {
                $arg = array_shift($argv) ?? '';
                $arg = strval($arg);
                $arg = mb_convert_encoding($arg, 'UTF-8', $encoding);
                assert(is_string($arg));
                $padding_pre = '';
                $padding_post = '';
                
                // truncate $arg
                if ($precision !== '') {
                    $precision = intval(substr($precision, 1));
                    if ($precision > 0 && mb_strlen($arg, $encoding) > $precision) {
                        $arg = mb_substr($arg, 0, $precision, $encoding);
                    }
                }
                
                // define padding
                $size = (int)$size;
                if ($size > 0) {
                    $argLength = mb_strlen($arg, $encoding);
                    if ($argLength < $size) {
                        if ($filler === '') {
                            $filler = ' ';
                        }
                        if ($align == '-') {
                            $padding_post = str_repeat($filler, $size - $argLength);
                        } else {
                            $padding_pre = str_repeat($filler, $size - $argLength);
                        }
                    }
                }
                
                // escape % and pass it forward
                $newFormat .= $padding_pre . str_replace('%', '%%', $arg) . $padding_post;
            }
            else {
                // another type, pass forward
                $newFormat .= "%$sign$filler$align$size$precision$type";
                $newArgv[] = array_shift($argv);
            }
            $format = strval($post);
        }
        // Convert new format back from UTF-8 to the original encoding
        $newFormat = strval(mb_convert_encoding($newFormat, $encoding, 'UTF-8'));
        return vsprintf($newFormat, $newArgv);
    }
}
