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

namespace PhalconKit\Cache;

/**
 * PhalconKit cache service type.
 *
 * This wrapper currently delegates to Phalcon's cache implementation without
 * changing behavior. It exists so applications can type/register cache
 * services under the PhalconKit namespace, while still using native Phalcon
 * adapters, serializers, and cache semantics.
 *
 * @see https://docs.phalcon.io/5.17/cache/
 */
class Cache extends \Phalcon\Cache\Cache
{
}
