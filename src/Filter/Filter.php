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

namespace PhalconKit\Filter;

use PhalconKit\Filter\Sanitize\IPv4;
use PhalconKit\Filter\Sanitize\IPv6;
use PhalconKit\Filter\Sanitize\Json;
use PhalconKit\Filter\Sanitize\Md5;

/**
 * Phalcon filter service with PhalconKit sanitizers registered by default.
 *
 * The service keeps Phalcon's native filter behavior and adds named sanitizers
 * for md5-style lowercase hexadecimal tokens, JSON strings, and IP address
 * normalization. Consumers can use the declared magic methods through Phalcon's
 * filter API, or request the named filters directly through
 * `sanitize($value, [Filter::FILTER_JSON])`.
 *
 * Invalid values follow the sanitizer contract for their data type: JSON keeps
 * `null` as `null`, invalid JSON becomes `null`, and invalid IP addresses become
 * an empty string so form/request sanitization can collapse them to "no value".
 *
 * @see https://docs.phalcon.io/5.15/filter/
 *
 * @method string|null md5(string $input)
 * @method string|null json(?string $input = null)
 * @method string ipv4(?string $input = null)
 * @method string ipv6(?string $input = null)
 */
class Filter extends \Phalcon\Filter\Filter
{
    /**
     * Sanitizer alias for lowercase hexadecimal md5-style tokens.
     */
    public const string FILTER_MD5 = 'md5';
    
    /**
     * Sanitizer alias for strings that must already contain valid JSON.
     */
    public const string FILTER_JSON = 'json';
    
    /**
     * Sanitizer alias for IPv4 address validation/normalization.
     */
    public const string FILTER_IPV4 = 'ipv4';
    
    /**
     * Sanitizer alias for IPv6 address validation/normalization.
     */
    public const string FILTER_IPV6 = 'ipv6';
    
    /**
     * Register PhalconKit sanitizers after Phalcon initializes its mapper.
     *
     * Phalcon passes its service mapper during construction. Calling the parent
     * first preserves native filters, then the framework aliases are layered on
     * top so the DI `filter` service and standalone `FilterFactory` instances
     * expose the same custom sanitizers.
     *
     * @param array<string, string> $mapper Existing Phalcon filter mapper.
     *
     * @return void
     */
    #[\Override]
    protected function init(array $mapper): void
    {
        parent::init($mapper);
        
        $this->set(self::FILTER_MD5, Md5::class);
        $this->set(self::FILTER_JSON, Json::class);
        $this->set(self::FILTER_IPV4, IPv4::class);
        $this->set(self::FILTER_IPV6, IPv6::class);
    }
}
