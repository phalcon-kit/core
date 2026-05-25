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
 *
 * Use this class anywhere a native `Phalcon\Filter\Validation` instance is
 * expected. Framework-specific validators under `PhalconKit\Filter\Validation`
 * are designed to plug into this native validation flow without changing the
 * message collection or field binding semantics.
 *
 * @see https://docs.phalcon.io/5.13/filter-validation/
 */
class Validation extends \Phalcon\Filter\Validation
{
}
