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

use PhalconKit\Mvc\Controller\Traits\Interfaces\ExposeInterface;

/**
 * Contract for full resource controllers.
 *
 * `RestfulInterface` extends the base REST controller contract with exposure
 * helpers used by find/list/export actions. It represents controllers that are
 * expected to provide the package's full resource workflow rather than only
 * custom REST actions.
 */
interface RestfulInterface extends
    ExposeInterface,
    RestInterface
{
}
