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

use Phalcon\Support\Debug\Dump;

if (!function_exists('dump')) {
    /**
     * Dump values in a CLI-safe or browser-safe representation.
     *
     * CLI and phpdbg output is encoded as pretty JSON so command-line debugging
     * stays readable without HTML. Web output uses Phalcon's debug dumper to
     * preserve type details in a browser-friendly format. This helper does not
     * terminate execution; use `dd()` or `vdd()` for dump-and-die behavior.
     *
     * @param mixed ...$params Values to inspect.
     *
     * @return void
     */
    function dump(...$params): void
    {
        if (in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            echo json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo PHP_EOL;
        } else {
            $dump = (new Dump([], true));
            foreach ($params as $param) {
                echo $dump->variable($param);
            }
        }
    }
}

if (!function_exists('exit_500')) {
    /**
     * Terminate execution with a 500 Internal Server Error response code.
     *
     * In web SAPIs, the response code is set to 500 when headers are still
     * mutable. In CLI/phpdbg the helper simply exits with status code 1. This is
     * intended for debugging helpers and unrecoverable bootstrap failures, not
     * for normal exception/control-flow handling.
     *
     * @return void
     */
    function exit_500(): void
    {
        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) && !headers_sent()) {
            http_response_code(500);
        }
        exit(1);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump values and terminate execution as an error.
     *
     * This is the framework's dump-and-die helper. It uses `dump()` for output
     * formatting, then delegates to `exit_500()` for web response/error status.
     *
     * @param mixed ...$params Values to inspect before termination.
     *
     * @return void
     */
    function dd(...$params): void
    {
        dump(...$params);
        exit_500();
    }
}

if (!function_exists('vdd')) {
    /**
     * Dump values through native `var_dump()` and terminate execution.
     *
     * This helper is intentionally lower-level than `dd()`: it bypasses the
     * Phalcon debug dumper so edge cases involving object debug handlers,
     * resources, or raw PHP output can be inspected directly.
     *
     * @param mixed ...$params Values to inspect before termination.
     *
     * @return void
     */
    function vdd(...$params): void
    {
        /**
         * @psalm-suppress ForbiddenCode
         */
        var_dump(...$params);
        exit_500();
    }
}
