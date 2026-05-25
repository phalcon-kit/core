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

namespace PhalconKit\Mvc\Controller;

use PhalconKit\Mvc\Controller\Traits\Interfaces\BehaviorInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\DebugInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\FractalInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\ParamsInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\RestResponseInterface;

/**
 * Base contract for PhalconKit REST controllers.
 *
 * The interface composes the controller capabilities required by the REST
 * action traits: debug-state checks, event behavior attachment, request
 * parameter filtering, Fractal transformations, and response normalization.
 * Concrete controllers normally extend {@see Rest}; this contract is useful
 * for typed decorators, tests, and application base controllers.
 */
interface RestInterface extends
    DebugInterface,
    BehaviorInterface,
    ParamsInterface,
    FractalInterface,
    RestResponseInterface
{
}
