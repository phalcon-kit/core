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

namespace PhalconKit;

use PhalconKit\Di\Injectable;
use PhalconKit\Support\Options\Options;
use PhalconKit\Support\Options\OptionsInterface;

/**
 * Allow to manage and lookup the locale for the localisation
 * @property string|null $locale The current locale
 */
class Locale extends Injectable implements OptionsInterface
{
    use Options;
    
    /**
     * Default (router only)
     */
    public const string MODE_DEFAULT = 'default';
    
    /**
     * Router
     */
    public const string MODE_ROUTE = 'route';
    
    /**
     * Router -> http
     */
    public const string MODE_HTTP = 'http';
    
    /**
     * Router -> session -> http
     */
    public const string MODE_SESSION = 'session';
    
    /**
     * Locale mode
     * Locale::MODE_DEFAULT 'default' (Router -> http)
     * Locale::MODE_SESSION 'session' (Router -> session -> http)
     */
    public string $mode = self::MODE_DEFAULT;
    
    /**
     * The actual locale that was picked
     * @var string|null
     */
    public ?string $locale = null;
    
    /**
     * Session key for storing the locale
     * @var string $sessionKey The session key for storing the locale.
     */
    public string $sessionKey = 'phalcon-kit-locale';
    
    /**
     * Default locale
     *
     * This variable holds the default locale value for the application.
     * If no locale is explicitly specified, this value will be used.
     *
     * @var string $default
     */
    public string $default = 'en';
    
    /**
     * Array of allowed languages.
     *
     * @var array $allowed An array of allowed languages.
     */
    public array $allowed = ['en'];
    
    /**
     * Initializes the object by setting its properties based on the provided options.
     *
     * This method retrieves the values of the sessionKey, allowed, default, and mode options using the getOption()
     * method. If these options are not provided, the default values specified in the class properties are used instead.
     *
     * It then sets the obtained values to the corresponding class properties using the appropriate setter methods,
     * namely setAllowed(), setDefault(), and setMode(). Additionally, it assigns the obtained sessionKey value directly
     * to the sessionKey property.
     *
     * Finally, the initialize() method prepares the default value by calling the prepare() method with the getDefault()
     * method as its parameter.
     *
     * @return void
     */
    #[\Override]
    public function initialize(): void
    {
        $this->sessionKey = $this->getOption('sessionKey', $this->sessionKey);
        $this->setAllowed($this->getOption('allowed', $this->allowed));
        $this->setDefault($this->getOption('default', $this->default));
        $this->setMode($this->getOption('mode', $this->mode));
        $this->prepare($this->getDefault());
    }
    
    /**
     * Alias of the getLocale() method
     */
    public function get(): ?string
    {
        return $this->getLocale();
    }
    
    /**
     * Retrieves the locale value of the object.
     *
     * This method returns the value of the locale property, which represents the current locale of the object.
     * The locale property is set using the setLocale() method or may be null if no locale is set.
     *
     * @return string|null The locale value of the object, or null if no locale is set.
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }
    
    /**
     * Set the current locale value
     */
    public function setLocale(?string $locale = null): void
    {
        $this->locale = $this->lookup($locale);
    }
    
    /**
     * Get the default locale
     */
    public function getDefault(): string
    {
        return $this->default;
    }
    
    /**
     * Set the default locale value
     */
    public function setDefault(string $locale): void
    {
        $this->default = $locale;
    }
    
    /**
     * Get the list of possible locale
     */
    public function getAllowed(): array
    {
        return $this->allowed;
    }
    
    /**
     * Set the allowed locale
     */
    public function setAllowed(array $allowed): void
    {
        $this->allowed = array_values(array_unique($allowed));
    }
    
    /**
     * Get the defined mode
     */
    public function getMode(): string
    {
        return $this->mode;
    }
    
    /**
     * Set the mode
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }
    
    /**
     * Prepare and set and return the locale based on the defined mode
     */
    public function prepare(?string $default = null): ?string
    {
        $locale = match ($this->mode) {
            self::MODE_SESSION =>
                $this->getFromRoute() ??
                $this->getFromSession() ??
                $this->getFromHttp() ??
                $default,
            self::MODE_HTTP =>
                $this->getFromRoute() ??
                $this->getFromHttp() ??
                $default,
            default =>
                $this->getFromRoute() ??
                $default,
        };
        
        $locale ??= $this->locale;
        $this->setLocale($locale);
        $this->saveIntoSession($locale);
        return $this->getLocale();
    }
    
    /**
     * Retrieves the locale from the route
     */
    public function getFromRoute(?string $default = null): ?string
    {
        return $this->lookup($this->router->getParams()['locale'] ?? $default);
    }
    
    /**
     * Retrieves the locale from the dispatcher
     */
    public function getFromDispatcher(?string $default = null): ?string
    {
        return $this->lookup($this->dispatcher->getParams()['locale'] ?? $default);
    }
    
    /**
     * Retrieves the locale from the session
     */
    public function getFromSession(?string $default = null): ?string
    {
        return $this->lookup($this->session->get($this->sessionKey, $default));
    }
    
    /**
     * Retrieves the locale from the request
     * of getBestLanguage() header
     * or HTTP_ACCEPT_LANGUAGE header
     */
    public function getFromHttp(?string $default = null): ?string
    {
        $default ??= $this->getDefault();
        $header = $this->request->getHeader('HTTP_ACCEPT_LANGUAGE');

        if (trim($header) === '') {
            return $this->lookup($default, null, false, $default);
        }

        $locale = $this->lookup($this->request->getBestLanguage());

        if ($locale !== null) {
            return $locale;
        }

        $accepted = \Locale::acceptFromHttp($header);
        $accepted = is_string($accepted) ? $accepted : $default;

        return $this->lookup($accepted, null, false, $default);
    }
    
    /**
     * Save locale into session if mode contain session handling
     */
    public function saveIntoSession(?string $locale = null, ?bool $force = false): void
    {
        $locale ??= $this->getLocale();
        
        // save into session
        if ($force || $this->mode === self::MODE_SESSION) {
            $this->session->set($this->sessionKey, $locale);
        }
    }
    
    /**
     * @param string|null $locale The locale to use as the language range when matching.
     * @param array|null $allowed An array containing a list of language tags to compare to locale. Maximum 100 items allowed.
     * @param bool $canonicalize If true, the arguments will be converted to canonical form before matching.
     * @param string|null $default The locale to use if no match is found.
     * @return string|null The closest matching language tag or default value.
     */
    public function lookup(?string $locale = null, ?array $allowed = null, bool $canonicalize = false, ?string $default = null): ?string
    {
        if (is_null($locale)) {
            return null;
        }
        
        $allowed ??= $this->getAllowed();
        
        // lookup first
        $lookup = \Locale::lookup($allowed, $locale, $canonicalize, $default);
        
        // base locale found without the region
        $force = false;
        if (isset($lookup) && ($locale === $lookup || strlen($lookup) === 2)) {
            $locale = $lookup;
            $force = true;
        }
        
        // lookup for the first configured region based on the locale without region
        if (empty($lookup) || $force) {
            // matches all the possible regions from the locale
            $matches = array_filter($allowed, function ($haystack) use ($locale) {
                $needle = $locale . '_';
                return stripos($haystack, $needle) === 0;
            });
            
            // some matches
            if (count($matches)) {
                // lookup again with the first match
                $lookup = \Locale::lookup($matches, array_shift($matches), $canonicalize, $default);
            }
            else {
                // otherwise keep the lookup if set or set the default if not
                $lookup = empty($lookup) ? $default : $lookup;
            }
        }
        
        return empty($lookup) ? null : $lookup;
    }
}
