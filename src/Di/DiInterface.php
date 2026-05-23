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

interface DiInterface extends \Phalcon\Di\DiInterface
{
    /**
     * @template T of object
     * @param class-string<T> $expectedType
     * @return T
     */
    public function getTyped(string $name, string $expectedType, mixed $parameters = null): object;

    public function getConfig(string $name = 'config'): ConfigInterface;
}
