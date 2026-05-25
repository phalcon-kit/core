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

namespace PhalconKit\Provider\DatabaseReadOnly;

/**
 * Registers the read-only database connection service.
 *
 * This provider reuses the base database provider but forces the configured
 * `readonly` driver and exposes it as `dbr`. Applications commonly use this
 * service for replicas or reporting queries that should not touch the primary
 * write connection.
 */
class ServiceProvider extends \PhalconKit\Provider\Database\ServiceProvider
{
    protected ?string $driverName = 'readonly';
    protected string $serviceName = 'dbr';
}
