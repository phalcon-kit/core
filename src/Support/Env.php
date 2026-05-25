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

use Dotenv\Dotenv;
use PhalconKit\Exception\ConfigurationException;

/**
 * Loads dotenv files and exposes normalized environment values.
 *
 * The helper keeps dotenv configuration in static state because bootstrap
 * config objects need environment values before the DI container exists. Values
 * loaded from dotenv are cached in {@see $vars}; callers can also set values
 * directly in tests or specialized bootstraps. `get()` normalizes common
 * string values (`true`, `false`, integers, and floats) so config defaults do
 * not need to repeat basic scalar casting.
 */
class Env
{
    /**
     * Last Dotenv loader created by {@see load()}.
     *
     * @var Dotenv|null
     */
    public static ?Dotenv $dotenv = null;
    
    /**
     * Cached dotenv values and explicit test/runtime overrides.
     *
     * @var array<string, mixed>
     */
    public static array $vars = [];
    
    /**
     * Directories searched for dotenv files.
     *
     * @var string[]|string|null
     */
    public static string|array|null $paths = null;
    
    /**
     * Dotenv file names to load from the configured paths.
     *
     * @var string[]|string|null
     */
    public static string|array|null $names = null;
    
    /**
     * Dotenv factory type: mutable, immutable, unsafe-mutable, or unsafe-immutable.
     */
    public static string $type = 'mutable';
    
    /**
     * Whether dotenv should stop after the first matching file.
     */
    public static bool $shortCircuit = true;
    
    /**
     * Optional file encoding passed to Dotenv.
     */
    public static ?string $fileEncoding = null;
    
    /**
     * Configure and load dotenv files.
     *
     * Null parameters reuse the current static settings. When no paths have
     * been configured, {@see setPaths()} derives a path from `ENV_PATH`,
     * `ROOT_PATH`, `APP_PATH`, or the current working directory. Loaded values
     * are stored in {@see $vars} and also returned through the Dotenv instance.
     *
     * @param string|array|null $paths The paths to search for dotenv files.
     * @param string|array|null $names The names of the dotenv files to load.
     * @param bool|null $shortCircuit Whether to stop loading dotenv files after finding the first one.
     * @param string|null $fileEncoding The encoding of the dotenv files.
     * @param string|null $type The type of dotenv files to load.
     *
     * @return Dotenv The loaded Dotenv instance.
     */
    public static function load(string|array|null $paths = null, string|array|null $names = null, ?bool $shortCircuit = true, ?string $fileEncoding = null, ?string $type = null): Dotenv
    {
        self::setPaths($paths);
        if ($names !== null || self::getNames() === null) {
            self::setNames($names);
        }
        self::setShortCircuit($shortCircuit);
        self::setFileEncoding($fileEncoding);
        self::setType($type);
        
        $type ??= self::getType();
        $paths ??= self::getPaths();
        $names ??= self::getNames();
        $shortCircuit ??= self::getShortCircuit();
        $fileEncoding ??= self::getFileEncoding();
        
        $dotenv = Dotenv::{'create' . $type}($paths, $names, $shortCircuit, $fileEncoding);
        self::$vars = $dotenv->safeLoad();
        self::$dotenv = $dotenv;
        
        return $dotenv;
    }
    
    /**
     * Return the configured dotenv search paths.
     *
     * @return string|string[]|null Configured paths or null before load/setup.
     */
    public static function getPaths(): string|array|null
    {
        return self::$paths;
    }
    
    /**
     * Set dotenv search paths.
     *
     * Passing null asks the helper to derive a path from known bootstrap
     * constants. `APP_PATH` is converted to its parent directory because app
     * paths usually point to the application source folder rather than the
     * project root where `.env` normally lives.
     */
    public static function setPaths(string|array|null $paths = null): void
    {
        if (!isset($paths)) {
            $paths = [];
            foreach (['ENV_PATH', 'ROOT_PATH', 'APP_PATH'] as $constant) {
                if (defined($constant)) {
                    $path = constant($constant);
                    if (!is_null($path)) {
                        $paths [] = $constant === 'APP_PATH' ? dirname($path) : $path;
                        break;
                    }
                }
            }
            if (empty($paths)) {
                $paths [] = getcwd();
            }
        }
        self::$paths = $paths;
    }
    
    /**
     * Return dotenv file names loaded from the configured paths.
     *
     * @return string|string[]|null Configured file names.
     */
    public static function getNames(): string|array|null
    {
        return self::$names;
    }
    
    /**
     * Set dotenv file names.
     *
     * Passing null resets the loader to the conventional `.env` file name.
     */
    public static function setNames(string|array|null $names): void
    {
        self::$names = $names ?? ['.env'];
    }
    
    /**
     * Return the Dotenv factory suffix for the configured loader type.
     *
     * Dotenv exposes static factories such as `createMutable()` and
     * `createUnsafeImmutable()`. This method converts the stored type string
     * into the suffix used by {@see load()}.
     *
     * @return string Dotenv factory suffix.
     *
     * @throws ConfigurationException When the configured environment loader
     *     type is unsupported.
     */
    public static function getType(): string
    {
        return match (strtolower(self::$type)) {
            'mutable' => 'Mutable',
            'immutable' => 'Immutable',
            'unsafe-mutable' => 'UnsafeMutable',
            'unsafe-immutable' => 'UnsafeImmutable',
            default => throw new ConfigurationException('Unsupported Env::$type defined'),
        };
    }
    
    /**
     * Set the Dotenv loader type.
     *
     * Invalid values are normalized to `mutable` for compatibility with older
     * bootstraps. A stricter invalid-type exception is tracked as a future
     * design question because changing this default could break existing
     * deployments.
     *
     * @param string|null $type Loader type: `mutable`, `immutable`,
     *     `unsafe-mutable`, or `unsafe-immutable`.
     */
    public static function setType(?string $type = null): void
    {
        $domain = ['mutable', 'immutable', 'unsafe-mutable', 'unsafe-immutable'];
        self::$type = isset($type) && in_array(strtolower($type), $domain, true) ? strtolower($type) : 'mutable';
    }
    
    /**
     * Return whether dotenv loading stops after the first matching file.
     *
     * @return bool Current short-circuit setting.
     */
    public static function getShortCircuit(): bool
    {
        return self::$shortCircuit;
    }
    
    /**
     * Set whether dotenv loading stops after the first matching file.
     *
     * @param bool|null $shortCircuit Null restores the default true value.
     */
    public static function setShortCircuit(?bool $shortCircuit = true): void
    {
        self::$shortCircuit = $shortCircuit ?? true;
    }
    
    /**
     * Return the configured dotenv file encoding.
     *
     * @return string|null Encoding passed to Dotenv, or null for its default.
     */
    public static function getFileEncoding(): ?string
    {
        return self::$fileEncoding;
    }
    
    /**
     * Set the dotenv file encoding.
     *
     * @param string|null $fileEncoding Encoding passed to Dotenv, or null for
     *     its default.
     */
    public static function setFileEncoding(?string $fileEncoding = null): void
    {
        self::$fileEncoding = $fileEncoding;
    }
    
    /**
     * Return the current Dotenv instance, loading defaults on first use.
     *
     * @return Dotenv Active Dotenv loader.
     */
    public static function getDotenv(): Dotenv
    {
        return self::$dotenv ?? self::load();
    }
    
    /**
     * Return an environment value with simple scalar normalization.
     *
     * String values equal to `true` or `false` are returned as booleans.
     * Numeric strings are returned as integers or floats. Other values are
     * returned unchanged, and missing keys return the caller-provided default.
     *
     * @param string $key Environment key.
     * @param mixed $default Fallback when the key is not loaded.
     *
     * @return mixed Normalized environment value or fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::getDotenv();
        
        if (!isset(self::$vars[$key])) {
            return $default;
        }
        
        $value = self::$vars[$key];
        
        if (!is_string($value)) {
            return $value;
        }
        
        // Check for boolean values
        if (strtolower($value) === 'true') {
            return true;
        } elseif (strtolower($value) === 'false') {
            return false;
        }
        
        // Check for numeric values
        if (is_numeric($value)) {
            // Floats
            if (str_contains($value, '.')) {
                return floatval($value);
            }
            
            // Integers
            return intval($value);
        }
        
        return $value;
    }
    
    /**
     * Set or override one cached environment value.
     *
     * This affects PhalconKit's cached environment store only; it does not call
     * `putenv()` or mutate `$_ENV`.
     *
     * @param string $key Environment key.
     * @param mixed $value Value to store.
     */
    public static function set(string $key, mixed $value): void
    {
        self::$vars[$key] = $value;
    }
}
