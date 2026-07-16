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

use Phalcon\Messages\Message;
use PhalconKit\Exception\HttpException;
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
    use ErrorActions {
        errorAction as private setErrorStatusAction;
    }
    use StatusCode;

    /**
     * Render the configured HTTP-exception route through the REST envelope.
     *
     * The dispatcher owns status validation and preserves the exception as a
     * named route parameter. Only HttpException messages are exposed here;
     * fatal exceptions remain private and use {@see fatalAction()}.
     */
    public function errorAction(?int $code = null, ?string $message = null): void
    {
        $this->setErrorStatusAction($code, $message);

        $exception = $this->dispatcher->getParam('exception');
        if (!$exception instanceof HttpException) {
            return;
        }

        $this->setRestViewVar(self::REST_VIEW_MESSAGES, [
            new Message(
                $exception->getMessage(),
                '',
                'HttpException',
                $this->response->getStatusCode()
            ),
        ]);
    }
}
