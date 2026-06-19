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

namespace PhalconKit\Modules\Api\Controllers;

use PhalconKit\Mvc\Controller\Rest;
use PhalconKit\Mvc\Controller\Traits\Actions\ErrorActions;
use PhalconKit\Mvc\Controller\Traits\StatusCode;

/**
 * API error endpoint without model-backed REST actions.
 *
 * Error routes are dispatch targets for status rendering only. They must not
 * inherit the `Restful` CRUD/query surface because routes such as
 * `/api/error/save` would otherwise attempt to infer and load an `Error` model
 * instead of returning through the status action flow.
 */
class ErrorController extends Rest
{
    use ErrorActions;
    use StatusCode;
}
