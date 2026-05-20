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

/**
 * This class provides utilities for managing environment variables and loading .env files.
 * It allows for easy access to environment variables and provides methods for setting and getting values.
 * It also initializes a Dotenv instance and loads .env files based on specified configurations.
 *
 * @package PhalconKit\Support
 */
class Env
{
    /**
     * Represents the instance of the Dotenv class.
     *
     * The Dotenv class is responsible for loading environment variables from the
     * ".env" file into the application.
     *
     * @var ?Dotenv
     */
    public static ?Dotenv $dotenv = null;
    
    /**
     * Represents an array of variables.
     *
     * The $vars variable is used to store a collection of key-value pairs,
     * where each key represents a variable name and the corresponding value
     * represents the value of that variable.
     *
     * @var array
     */
    public static array $vars = [];
    
    /**
     * Holds the value of the paths.
     *
     * This variable represents the value of the paths and is initially set to null.
     * It can be assigned a different value during the runtime of the program.
     *
     * @var string[]|string|null
     */
    public static string|array|null $paths = null;
    
    /**
     * Represents the array of filenames.
     *
     * The $names variable is an array that holds the filenames of the ".env" file(s) to be loaded.
     * This variable is used as an argument in the Dotenv class to specify the filenames to load.
     *
     * @var string[]|string|null
     */
    public static string|array|null $names = null;
    
    /**
     * Represents the type of data being declared.
     *
     * The $type variable is a string that indicates the mutability of the data. It can
     * have two possible values: "mutable" or "immutable".
     *
     * @var string
     */
    public static string $type = 'mutable';
    
    /**
     * Represents a boolean flag that enables short-circuiting in the code.
     *
     * When the $shortCircuit variable is set to true, it indicates that the code
     * should perform short-circuit evaluation. Short-circuit evaluation allows
     * skipping the evaluation of subsequent conditions in a logical expression if
     * the final result can be determined early.
     *
     * @var bool
     */
    public static bool $shortCircuit = true;
    
    /**
     * Represents the encoding of the file.
     *
     * The $fileEncoding variable is used to store the encoding of the file that
     * will be processed by the application. It is initially set to null and will be
     * updated with the actual encoding value during the file processing.
     *
     * @var string|null
     */
    public static ?string $fileEncoding = null;
    
    /**
     * Initializes the Dotenv instance with the specified configurations
     * and returns the loaded instance.
     *
     * @param string|array|null $paths The paths to search for dotenv files.
     * @param string|array|null $names The names of the dotenv files to load.
     * @param bool|null $shortCircuit Whether to stop loading dotenv files after finding the first one.
     * @param string|null $fileEncoding The encoding of the dotenv files.
     * @param string|null $type The type of dotenv files to load.
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
     * Retrieves an array of paths.
     * If the paths array is not yet created, it will be loaded and returned.
     *
     * @return string|string[]|null The array of paths.
     */
    public static function getPaths(): string|array|null
    {
        return self::$paths;
    }
    
    /**
     * Sets the paths for the application. If no paths are provided,
     * the paths will be set to null.
     *
     * @param string|string[]|null $paths The paths to be set for the application.
     *                                 If null is provided, the paths will be set to null.
     *                                 Default is null.
     *
     * @return void
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
     * Get .env file names
     * @return string|string[]|null
     */
    public static function getNames(): string|array|null
    {
        return self::$names;
    }
    
    /**
     * Sets the names array. If the specified array is null, the existing names array will be cleared.
     *
     * @param string|string[]|null $names The array of names. If null, the existing names array will be cleared.
     *
     * @return void
     */
    public static function setNames(string|array|null $names): void
    {
        self::$names = $names ?? ['.env'];
    }
    
    /**
     * Retrieves the type of the instance. If the type is not set, it will default to 'mutable'.
     * The type is then transformed into a camel case string and the first letter is capitalized.
     *
     * @return string The type of the instance.
     * @throws \Exception
     */
    public static function getType(): string
    {
        return match (strtolower(self::$type)) {
            'mutable' => 'Mutable',
            'immutable' => 'Immutable',
            'unsafe-mutable' => 'UnsafeMutable',
            'unsafe-immutable' => 'UnsafeImmutable',
            default => throw new \Exception('Unsupported Env::$type defined'),
        };
    }
    
    /**
     * Sets the type of the object. If the type is not provided or is not one of the allowed types,
     * the type will be set to 'mutable' by default.
     *
     * @param string|null $type The type of the object. Available types: 'mutable', 'immutable', 'unsafe-mutable', 'unsafe-immutable'.
     * @return void
     */
    public static function setType(?string $type = null): void
    {
        $domain = ['mutable', 'immutable', 'unsafe-mutable', 'unsafe-immutable'];
        self::$type = isset($type) && in_array(strtolower($type), $domain, true) ? strtolower($type) : 'mutable';
    }
    
    /**
     * Retrieves the short circuit value. If the short circuit value is not yet set,
     * it will return the default value.
     *
     * @return bool The short circuit value.
     */
    public static function getShortCircuit(): bool
    {
        return self::$shortCircuit;
    }
    
    /**
     * Sets the value of the shortCircuit property.
     *
     * @param bool $shortCircuit The new value for the shortCircuit property.
     * @return void
     */
    public static function setShortCircuit(?bool $shortCircuit = true): void
    {
        self::$shortCircuit = $shortCircuit ?? true;
    }
    
    /**
     * Retrieves the file encoding. If the encoding is not yet set, it will return null.
     *
     * @return ?string The file encoding.
     */
    public static function getFileEncoding(): ?string
    {
        return self::$fileEncoding;
    }
    
    /**
     * Sets the file encoding for the env file. If no file encoding is specified,
     * the default encoding will be used.
     *
     * @param string|null $fileEncoding The file encoding to be set. Defaults to null.
     * @return void
     */
    public static function setFileEncoding(?string $fileEncoding = null): void
    {
        self::$fileEncoding = $fileEncoding;
    }
    
    /**
     * Retrieves the Dotenv instance. If the instance is not yet created,
     * it will be loaded and returned.
     *
     * @return Dotenv The Dotenv instance.
     */
    public static function getDotenv(): Dotenv
    {
        return self::$dotenv ?? self::load();
    }
    
    /**
     * Gets the value of an environment variable. Pass the $default for fallback value.
     * @param string $key Key to get
     * @param mixed $default Value to fallback if the key can't be found
     * @return mixed Return the environment variable value or the default value
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
     * Set an environment variable
     * @param string $key Key to set
     * @param mixed $value Value to set
     */
    public static function set(string $key, mixed $value): void
    {
        self::$vars[$key] = $value;
    }
}
