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

namespace PhalconKit\Mvc\Controller\Traits\Actions;

use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractStatusCode;

/**
 * Default Error Actions
 */
trait ErrorActions
{
    use AbstractStatusCode;
    
    /**
     * Render a generic error using an explicit or already-selected status.
     *
     * Dispatcher listeners can set the shared response status before forwarding
     * here. Direct callers may still provide a status and optional reason phrase;
     * otherwise the action falls back to HTTP 500.
     */
    public function errorAction(?int $code = null, ?string $message = null): void
    {
        if ($code === null) {
            $code = $this->response->getStatusCode() ?: 500;
            $message ??= $this->response->getReasonPhrase();
        }
        $this->setStatusCode($code, $message);
    }
    
    /**
     * Http Status Code 400
     * bad-request
     */
    public function badRequestAction(): void
    {
        $this->setStatusCode(400);
    }
    
    /**
     * Http Status Code 401
     * unauthorized
     */
    public function unauthorizedAction(): void
    {
        $this->setStatusCode(401);
    }
    
    /**
     * Http Status Code 403
     * forbidden
     */
    public function forbiddenAction(): void
    {
        $this->setStatusCode(403);
    }
    
    /**
     * Http Status Code 404
     * not-found
     */
    public function notFoundAction(): void
    {
        $this->setStatusCode(404);
    }
    
    /**
     * Http Status Code 500
     * fatal
     */
    public function fatalAction(): void
    {
        $this->setStatusCode(500);
    }
    
    /**
     * Http Status Code 503
     * maintenance
     */
    public function maintenanceAction(): void
    {
        $this->setStatusCode(503);
    }
}
