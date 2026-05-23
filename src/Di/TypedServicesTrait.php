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

namespace PhalconKit\Di;

use PhalconKit\Config\ConfigInterface;
use PhalconKit\Exception\ServiceException;

trait TypedServicesTrait
{
    /**
     * @template T of object
     * @param class-string<T> $expectedType
     * @return T
     */
    public function getTyped(string $name, string $expectedType, mixed $parameters = null): object
    {
        try {
            $service = $this->get($name, $parameters);
        }
        catch (\Throwable $e) {
            throw new ServiceException(sprintf(
                'Could not resolve DI service "%s".',
                $name
            ), previous: $e);
        }

        if (!$service instanceof $expectedType) {
            throw new ServiceException(sprintf(
                'Expected DI service "%s" to be an instance of "%s"; got "%s".',
                $name,
                $expectedType,
                get_debug_type($service)
            ));
        }

        return $service;
    }

    public function getConfig(string $name = 'config'): ConfigInterface
    {
        return $this->getTyped($name, ConfigInterface::class);
    }
}
