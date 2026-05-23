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

namespace PhalconKit\Di\FactoryDefault;

use PhalconKit\Di\DiInterface;
use PhalconKit\Di\TypedServicesTrait;

class Cli extends \Phalcon\Di\FactoryDefault\Cli implements DiInterface
{
    use TypedServicesTrait;
}
