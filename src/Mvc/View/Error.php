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

namespace PhalconKit\Mvc\View;

use Phalcon\Di\Injectable;

/**
 * View event listener reserved for framework-level view errors.
 *
 * The current implementation intentionally carries no event handlers. It gives
 * the view provider a stable PhalconKit listener type that applications can
 * extend or replace later without changing the provider wiring contract.
 */
class Error extends Injectable
{
}
