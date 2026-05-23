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
 * Small PHP runtime helpers used during bootstrap.
 *
 * These helpers centralize runtime concerns that must be configured before the
 * application handles a request, such as SAPI checks, proxy HTTPS detection,
 * debugging INI flags, locale, encoding, memory limit, and execution timeout.
 */
class Php
{
    /**
     * Determine whether a SAPI value represents a command-line runtime.
     *
     * `phpdbg` is treated as CLI so test runners and debuggers follow the same
     * bootstrap path as normal console commands.
     *
     * @param string $sapi PHP SAPI name. Defaults to the current process SAPI.
     *
     * @return bool True for CLI-like SAPIs, false for web SAPIs.
     */
    public static function isCli(string $sapi = PHP_SAPI): bool
    {
        return in_array($sapi, ['cli', 'phpdbg'], true);
    }
    
    /**
     * Promote trusted proxy HTTPS information into `$_SERVER['HTTPS']`.
     *
     * Applications behind a reverse proxy can call this during bootstrap after
     * deciding that `HTTP_X_FORWARDED_PROTO` is trustworthy. When the forwarded
     * protocol starts with `https`, Phalcon and PHP helpers that inspect
     * `$_SERVER['HTTPS']` will see the request as secure.
     *
     * @return void
     */
    public static function trustForwardedProto(): void
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            if (str_starts_with($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https')) {
                $_SERVER['HTTPS'] = 'on';
            }
        }
    }
    
    /**
     * Enable or disable PHP error display for the current process.
     *
     * Passing true enables full error reporting and display. Passing false or
     * null disables display while keeping `error_reporting(-1)`, which preserves
     * reporting for logs without exposing errors in responses.
     *
     * @param bool|null $debug Whether response-visible debug output should be
     *     enabled.
     *
     * @return void
     */
    public static function debug(?bool $debug = null): void
    {
        if ($debug) {
            // Enabling error reporting and display
            error_reporting(E_ALL);
            ini_set('display_startup_errors', '1');
            ini_set('display_errors', '1');
        } else {
            // Disabling error reporting and display
            error_reporting(-1);
            ini_set('display_startup_errors', '0');
            ini_set('display_errors', '0');
        }
    }
    
    /**
     * Apply process-wide PHP defaults used by PhalconKit applications.
     *
     * Missing values are filled with conservative framework defaults. This
     * method changes global PHP runtime state, so applications should call it
     * once during bootstrap before handling requests or starting long-running
     * workers.
     *
     * @param array{
     *     timezone?: non-empty-string,
     *     encoding?: non-empty-string,
     *     locale?: non-empty-string,
     *     memoryLimit?: non-empty-string,
     *     timeoutLimit?: int|string
     * } $config Runtime options to apply.
     *
     * @return void
     */
    public static function set(array $config = []): void
    {
        $config['timezone'] ??= 'America/Montreal';
        $config['encoding'] ??= 'UTF-8';
        $config['locale'] ??= 'en_CA';
        $config['memoryLimit'] ??= '256M';
        $config['timeoutLimit'] ??= '60';
        
        date_default_timezone_set($config['timezone']);
        setlocale(LC_ALL, $config['locale'] . '.' . $config['encoding']);
        mb_internal_encoding($config['encoding']);
        mb_http_output($config['encoding']);
        
        ini_set('memory_limit', $config['memoryLimit']);
        ini_set('max_execution_time', (string)$config['timeoutLimit']);
        set_time_limit((int)$config['timeoutLimit']);
    }
}
