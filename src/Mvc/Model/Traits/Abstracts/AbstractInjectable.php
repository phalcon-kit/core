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

namespace PhalconKit\Mvc\Model\Traits\Abstracts;

use Phalcon\Di\DiInterface;
use PhalconKit\Di\ServiceResolver;

trait AbstractInjectable
{
    abstract public function setDI(DiInterface $di): void;
    
    abstract public function getDI(): DiInterface;

    /**
     * Resolve a typed service from the model's DI container.
     *
     * Model traits often run inside native Phalcon model lifecycle hooks, where
     * the inherited `getDI()` return type is only Phalcon's native DI contract.
     * This helper centralizes the PhalconKit DI boundary check and typed service
     * validation so individual traits do not need repetitive private wrappers
     * around `ServiceResolver::fromContainer()`.
     *
     * @template T of object
     * @param string $name DI service name to resolve.
     * @param class-string<T> $expectedType Required runtime service contract.
     * @param string|null $context Human-readable caller context for exception
     *     messages, such as `model hash helpers`.
     * @return T
     * @throws \PhalconKit\Exception\ServiceException When the model DI is not a
     *     PhalconKit DI, the service is missing, or the service does not match
     *     the expected type.
     */
    protected function getTypedService(string $name, string $expectedType, ?string $context = null): object
    {
        return ServiceResolver::fromContainer(
            $this->getDI(),
            $name,
            $expectedType,
            context: $context
        );
    }
}
