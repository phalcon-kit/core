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

namespace PhalconKit\Provider\DatabaseDynamic;

/**
 * Registers the dynamic-model database connection service.
 *
 * This provider reuses the base database provider but forces the configured
 * `dynamic` driver and exposes it as `dbd`. Dynamic models and generated
 * record controllers can use this service when their storage should be isolated
 * from the primary application database.
 */
class ServiceProvider extends \PhalconKit\Provider\Database\ServiceProvider
{
    protected ?string $driverName = 'dynamic';
    protected string $serviceName = 'dbd';
}
