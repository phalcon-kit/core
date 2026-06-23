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

use Phalcon\Filter\FilterInterface;
use PhalconKit\Filter\Sanitize\IPv4;
use PhalconKit\Filter\Sanitize\IPv6;
use PhalconKit\Filter\Sanitize\Json;
use PhalconKit\Filter\Sanitize\Md5;

/**
 * Factory for filter locators that include PhalconKit sanitizer aliases.
 *
 * Phalcon's filter factory owns the native sanitizer registry. This subclass
 * keeps that registry intact and appends PhalconKit's md5, JSON, IPv4, and IPv6
 * services so framework providers and tests can create equivalent filter
 * locators without duplicating the alias map.
 *
 * @see https://docs.phalcon.io/5.16/filter/
 */
class FilterFactory extends \Phalcon\Filter\FilterFactory
{
    /**
     * Build a PhalconKit filter locator with native and framework sanitizers.
     *
     * @return FilterInterface Filter instance suitable for registering as the
     *     DI `filter` service.
     */
    #[\Override]
    public function newInstance(): FilterInterface
    {
        return new Filter($this->getServices());
    }
    
    /**
     * Return the native Phalcon sanitizer map plus PhalconKit aliases.
     *
     * The returned array is passed directly to `Filter`, where the aliases are
     * resolved by Phalcon's locator. Applications that need additional filters
     * should normally register them through the filter provider config instead
     * of overriding this method.
     *
     * @return array<string, mixed> Map of sanitizer aliases to callable or class
     *     service definitions accepted by Phalcon's filter locator.
     */
    #[\Override]
    protected function getServices(): array
    {
        return array_merge(parent::getServices(), [
            Filter::FILTER_MD5 => Md5::class,
            Filter::FILTER_JSON => Json::class,
            Filter::FILTER_IPV4 => IPv4::class,
            Filter::FILTER_IPV6 => IPv6::class,
        ]);
    }
}
