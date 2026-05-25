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

namespace PhalconKit\Provider\Aws;

use Aws\Sdk;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the AWS SDK service.
 *
 * Options are read directly from the `aws` config section and passed to
 * `Aws\Sdk`. Keep region, credentials, endpoint overrides, and SDK-level
 * options in config so AWS clients created from this service share the same
 * runtime posture.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'aws';
    
    /**
     * Register the shared `aws` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->getConfig();
            $options = $config->pathToArray('aws') ?? [];
            
            return new Sdk($options);
        });
    }
}
