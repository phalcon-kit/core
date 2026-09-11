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
     * Render a forwarded authentication failure without resolving roles again.
     *
     * The rejected credential remains in the request. Attaching identity-based
     * behaviors here would repeat the failure instead of rendering the 401.
     * Other error routes retain the usual controller behavior hooks.
     */
    #[\Override]
    public function beforeExecuteRoute(): void
    {
        if (!$this->isUnauthorizedException()) {
            parent::beforeExecuteRoute();
        }
    }

    /**
     * Omit request/identity debug context from authentication-error responses.
     *
     * Besides re-entering token validation, debug context can contain the
     * rejected credential. This applies even when application debug is enabled.
     */
    #[\Override]
    public function isDebugEnabled(): bool
    {
        return !$this->isUnauthorizedException() && parent::isDebugEnabled();
    }

    /**
     * Make authentication failures uncacheable without reading identity state.
     *
     * @param array<array-key, mixed> $payload REST response envelope.
     * @param int $code HTTP response status.
     */
    #[\Override]
    protected function applyCacheHeaders(array $payload, int $code): void
    {
        if ($this->isUnauthorizedException()) {
            $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
            $this->response->setHeader('Expires', '0');
            $this->setVaryHeaders(false);
            return;
        }

        parent::applyCacheHeaders($payload, $code);
    }

    private function isUnauthorizedException(): bool
    {
        $exception = $this->dispatcher->getParameter('exception');
        return $exception instanceof HttpException && $exception->getCode() === 401;
    }

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

        $exception = $this->dispatcher->getParameter('exception');
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
