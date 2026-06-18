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

namespace PhalconKit\Mvc;

use PhalconKit\Dispatcher\DispatcherInterface;
use PhalconKit\Dispatcher\DispatcherTrait;

/**
 * MVC dispatcher with PhalconKit's shared dispatcher safeguards.
 *
 * The dispatcher keeps Phalcon's MVC dispatch behavior and mixes in the
 * framework forwarding protections and diagnostic export helpers from
 * `DispatcherTrait`.
 *
 * @see https://docs.phalcon.io/5.15/dispatcher/
 */
class Dispatcher extends \Phalcon\Mvc\Dispatcher implements DispatcherInterface
{
    use DispatcherTrait;
}
