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

namespace PhalconKit\Mvc\Dispatcher;

use JetBrains\PhpStorm\NoReturn;
use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Di\Injectable;

/**
 * Dispatcher listener for CORS and preflight requests.
 *
 * Register this listener on the MVC dispatcher events manager when an
 * application wants framework-level CORS handling before controller actions are
 * executed. CORS headers are read from `response.corsHeaders`; preflight
 * requests receive a 204 response immediately.
 */
class Preflight extends Injectable
{
    /**
     * Apply configured CORS headers and short-circuit preflight requests.
     *
     * @return void
     */
    public function beforeExecuteRoute(): void
    {
        if ($this->request->isCors()) {
            $origin = $this->request->getHeader('Origin');
            $headers = $this->config->pathToArray('response.corsHeaders') ?? [];
            $this->setCorsHeaders($this->response, $origin, $headers);
        }
        
        if ($this->request->isPreflight()) {
            $this->sendNoContent($this->response);
        }
    }
    
    /**
     * Set configured CORS headers on a response.
     *
     * The configured `Access-Control-Allow-Origin` value is treated specially:
     * wildcard or explicitly allowed origins are reflected as the current
     * request origin, while unrelated origins are ignored. Existing headers are
     * preserved so controllers or earlier listeners can override framework
     * defaults.
     *
     * @param ResponseInterface $response The response object to set the headers on.
     * @param string $origin The origin value to be checked against the allowed origins.
     * @param array<string, array<int, string>|bool|string> $headers Configured
     *     CORS header values.
     *
     * @return void
     */
    public function setCorsHeaders(ResponseInterface $response, string $origin, array $headers = []): void
    {
        // Set cors headers
        foreach ($headers as $headerKey => $headerValue) {
            if (!$response->hasHeader($headerKey) && !is_array($headerValue)) {
                // ignore Access-Control-Allow-Origin as we will add the header after
                if ($headerKey === 'Access-Control-Allow-Origin') {
                    continue;
                }
                
                // Ensure the bool values are sent as string
                if (is_bool($headerValue)) {
                    $headerValue = $headerValue ? 'true' : 'false';
                }
                
                // set the header
                $response->setHeader($headerKey, $headerValue);
            }
        }
        
        // Set origin value if origin is allowed
        $originKey = 'Access-Control-Allow-Origin';
        $allowedOrigins = $headers[$originKey] ?? null;
        if (!$response->hasHeader($originKey) &&
            ($allowedOrigins === '*' || (
                is_array($allowedOrigins) &&
                (in_array($origin, $allowedOrigins, true) || in_array('*', $allowedOrigins, true)))
            )
        ) {
            $response->setHeader($originKey, $origin);
        }
    }
    
    /**
     * Send an immediate 204 No Content response.
     *
     * This method terminates the process after sending the response because a
     * preflight request must not continue into controller execution.
     *
     * @param ResponseInterface $response The response object to send.
     *
     * @return void
     */
    #[NoReturn]
    public function sendNoContent(ResponseInterface $response): void
    {
        $response->setStatusCode(204)->send();
        exit(0);
    }
}
