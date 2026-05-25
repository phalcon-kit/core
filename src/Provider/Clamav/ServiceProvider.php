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

namespace PhalconKit\Provider\Clamav;

use PhalconKit\Di\DiInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Provider\AbstractServiceProvider;
use Socket\Raw\Factory;
use Xenolope\Quahog\Client;

/**
 * Registers the ClamAV client service.
 *
 * The provider connects to a ClamAV daemon using `clamav.address` and
 * `clamav.timeout`, defaulting to `tcp://127.0.0.1:3310` with a 30-second
 * timeout. It requires PHP's sockets extension because the Quahog client uses a
 * raw socket connection.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'clamav';
    
    /**
     * Register the shared `clamav` service.
     *
     * Runtime options may be passed when resolving the service manually; normal
     * applications should prefer config so scan behavior is consistent.
     *
     * @throws ServiceException When the sockets extension is not available.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?array $options = null) use ($di) {
            
            $config = $di->getConfig();
            if (!extension_loaded('sockets')) {
                throw new ServiceException('ClamAV service requires the sockets extension.');
            }
            
            $options ??= $config->pathToArray('clamav') ?? [];
            $address = $options['address'] ?? 'tcp://127.0.0.1:3310';
            $timeout = $options['timeout'] ?? 30;
            
            $socket = (new Factory())->createClient($address, $timeout);
            
            return new Client($socket, $timeout, PHP_NORMAL_READ);
        });
    }
}
