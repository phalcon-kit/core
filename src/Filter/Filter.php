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
 * for md5 hashes, JSON strings, and IP address normalization. Consumers can use
 * the declared magic methods through Phalcon's filter API or request the named
 * filters directly.
 *
 * @method string md5(string $input)
 * @method string json(string $input)
 * @method string ipv4(string $input)
 * @method string ipv6(string $input)
 */
class Filter extends \Phalcon\Filter\Filter
{
    public const string FILTER_MD5 = 'md5';
    
    public const string FILTER_JSON = 'json';
    
    public const string FILTER_IPV4 = 'ipv4';
    
    public const string FILTER_IPV6 = 'ipv6';
    
    /**
     * Register PhalconKit sanitizers after Phalcon initializes its mapper.
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
