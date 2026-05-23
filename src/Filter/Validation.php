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

namespace PhalconKit\Filter;

/**
 * PhalconKit validation service type.
 *
 * The implementation currently delegates to Phalcon's validator stack without
 * changing behavior. Keeping the wrapper in the framework namespace gives
 * applications a stable DI type to extend or replace when validation policy
 * needs to become application-specific.
 */
class Validation extends \Phalcon\Filter\Validation
{
}
