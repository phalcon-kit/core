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

namespace PhalconKit\Mvc\Controller\Traits;

use Phalcon\Http\ResponseInterface;
use Phalcon\Messages\MessageInterface;
use Phalcon\Mvc\Dispatcher;
use Countable;
use PhalconKit\Http\StatusCode as HttpStatusCode;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractDebug;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;
use PhalconKit\Support\Utils;

trait RestResponse
{
    use AbstractRestResponse;
    
    use AbstractDebug;
    use AbstractInjectable;
    use AbstractParams;
    
    /**
     * Return a normalized REST error response.
     *
     * This is the preferred exit path for controller failures that should use
     * the standard JSON envelope but carry a non-2xx HTTP status. The response
     * body remains caller-controlled so legacy actions can keep returning
     * `false`, `null`, or a custom payload while the envelope status and code
     * are still set consistently by {@see setRestResponse()}.
     *
     * @param int $code HTTP status code to expose in the response and payload.
     * @param string|null $status Optional status text; when null, the status is
     *     resolved from the current response or {@see HttpStatusCode}.
     * @param mixed $response Response body stored under the REST `response`
     *     envelope key.
     *
     * @return ResponseInterface The finalized Phalcon response instance.
     */
    public function setRestErrorResponse(int $code = 400, ?string $status = null, mixed $response = null): ResponseInterface
    {
        return $this->setRestResponse($response, $code, $status);
    }
    
    /**
     * Sending rest response as a http response.
     *
     * The JSON envelope is intentionally centralized here so REST actions can
     * focus on setting named view fields through {@see setRestViewVar()} and
     * {@see setRestViewVars()}. The envelope keys are public constants because
     * they are part of the API contract exposed to clients.
     *
     * @param mixed $response
     * @param ?int $code
     * @param ?string $status
     * @param int $jsonOptions
     * @param int $depth
     *
     * @return ResponseInterface
     */
    public function setRestResponse(mixed $response = null, ?int $code = null, ?string $status = null, int $jsonOptions = 0, int $depth = 512): ResponseInterface
    {
        // Determine code & status
        $code ??= $this->response->getStatusCode() ?: 200;
        $status ??= $this->response->getReasonPhrase() ?: (HttpStatusCode::getMessage($code) ?? '');
        
        $payload = $this->buildRestPayload($response, $code, $status);
        
        $this->applyCacheHeaders($payload, $code);
        if ($this->response->getStatusCode() === 304) {
            return $this->response;
        }
        
        // Finalize and return JSON response
        $this->response->setStatusCode($code, $status);
        return $this->response->setJsonContent($payload, $jsonOptions, $depth);
    }

    /**
     * Set one public view field for the REST response payload.
     *
     * The field is later serialized under the top-level `view` envelope key by
     * {@see setRestResponse()}. Standard framework actions should use the
     * `REST_VIEW_*` constants instead of repeating string literals so response
     * contracts remain discoverable and consistent.
     */
    protected function setRestViewVar(string $key, mixed $value): void
    {
        $this->view->setVar($key, $value);
    }

    /**
     * Set several public view fields for the REST response payload.
     *
     * @param array<string, mixed> $vars View fields to expose under the response
     *     envelope's `view` key.
     * @param bool $merge Whether to merge with existing view variables. This
     *     matches Phalcon's `setVars()` default behavior used by legacy actions.
     */
    protected function setRestViewVars(array $vars, bool $merge = true): void
    {
        $this->view->setVars($vars, $merge);
    }

    /**
     * Return view fields that are safe to serialize in a REST response.
     *
     * Phalcon keeps internal render data under `_`; that key is deliberately
     * stripped so controller actions cannot accidentally leak framework internals
     * through the public JSON envelope.
     *
     * @return array<string, mixed>
     */
    protected function getRestViewVars(): array
    {
        $view = $this->view->getParamsToView() ?? [];
        unset($view[self::REST_VIEW_INTERNAL]);

        return $view;
    }

    /**
     * Build the normalized REST JSON envelope.
     *
     * The shape is intentionally stable for backward compatibility:
     * `timestamp`, `status`, `code`, `response`, and `view` are always present,
     * while `debug` is added only when debug mode is enabled.
     *
     * @return array<string, mixed>
     */
    protected function buildRestPayload(mixed $response, int $code, string $status): array
    {
        $payload = [
            self::REST_PAYLOAD_TIMESTAMP => date('c'),
            self::REST_PAYLOAD_STATUS => $status,
            self::REST_PAYLOAD_CODE => $code,
            self::REST_PAYLOAD_RESPONSE => $response,
            self::REST_PAYLOAD_VIEW => $this->getRestViewVars(),
        ];

        if ($this->isDebugEnabled()) {
            $payload[self::REST_PAYLOAD_DEBUG] = $this->getDebugInfo();
        }

        return $payload;
    }

    /**
     * Resolve an HTTP status code for REST action failures carrying messages.
     *
     * Model, validation, and domain-rule failures normally include messages and
     * map to 422 Unprocessable Entity. Framework-generated REST failures can
     * attach explicit client-error codes to Phalcon messages; those 4xx codes
     * are preserved so actions do not collapse invalid request intent, missing
     * targets, forbidden operations, or conflicts into generic validation
     * responses. Server errors stay owned by thrown exceptions or explicit
     * controller calls to {@see setRestErrorResponse()}.
     *
     * A failure without messages is treated as malformed input by default. The
     * defaults can be overridden for actions that need a different legacy or
     * protocol-specific response.
     *
     * @param mixed $messages A Phalcon messages collection, iterable list,
     *     single message, or any legacy message payload returned by model/action
     *     code.
     * @param int $emptyStatusCode Status code used when no message payload is
     *     available.
     * @param int $defaultStatusCode Status code used when messages exist but no
     *     explicit HTTP status code is attached.
     */
    protected function getRestActionFailureStatusCode(
        mixed $messages,
        int $emptyStatusCode = 400,
        int $defaultStatusCode = 422
    ): int {
        if (!$this->hasRestActionMessages($messages)) {
            return $emptyStatusCode;
        }

        foreach (is_iterable($messages) ? $messages : [$messages] as $message) {
            $statusCode = $this->getRestActionMessageStatusCode($message);
            if ($statusCode !== null) {
                return $statusCode;
            }
        }

        return $defaultStatusCode;
    }

    /**
     * Determine whether a REST action failure carried any message payload.
     *
     * PHP objects are never empty for `empty()`, even when they implement
     * `Countable` and contain zero messages. Phalcon validation returns
     * `Phalcon\Messages\Messages`, so status resolution must check the
     * collection count instead of relying on PHP object truthiness.
     */
    protected function hasRestActionMessages(mixed $messages): bool
    {
        if ($messages instanceof Countable) {
            return count($messages) > 0;
        }

        if (is_array($messages)) {
            return count($messages) > 0;
        }

        if ($messages instanceof \Traversable) {
            foreach ($messages as $_message) {
                return true;
            }

            return false;
        }

        return (bool) $messages;
    }

    /**
     * Return a normalized REST error response for an action failure.
     *
     * This helper keeps REST actions from pre-mutating the response status and
     * then relying on {@see setRestResponse()} to pick that status back up. The
     * action still owns its public view fields; this method only resolves the
     * HTTP failure code from explicit message metadata and delegates the final
     * envelope to {@see setRestErrorResponse()}.
     *
     * @param mixed $messages A Phalcon messages collection, iterable list,
     *     single message, or any legacy message payload returned by model/action
     *     code.
     * @param mixed $response Response body stored under the REST `response`
     *     envelope key. Standard framework actions usually pass `false`.
     * @param int $emptyStatusCode Status code used when no message payload is
     *     available.
     * @param int $defaultStatusCode Status code used when messages exist but no
     *     explicit HTTP status code is attached.
     *
     * @return ResponseInterface The finalized Phalcon response instance.
     */
    protected function setRestActionFailureResponse(
        mixed $messages,
        mixed $response = false,
        int $emptyStatusCode = 400,
        int $defaultStatusCode = 422
    ): ResponseInterface {
        return $this->setRestErrorResponse(
            $this->getRestActionFailureStatusCode($messages, $emptyStatusCode, $defaultStatusCode),
            response: $response
        );
    }

    /**
     * Extract an explicit HTTP status code from one REST action message.
     *
     * Only Phalcon message codes in the HTTP client-error range are considered.
     * Normal validation messages often carry no code, or a non-HTTP code, and
     * server-error responses should come from exceptions or explicit controller
     * error handling instead of model/domain message metadata.
     *
     * @param mixed $message Candidate message value from a model/action failure.
     *
     * @return int|null Explicit 4xx HTTP status code when present and valid;
     *     otherwise null.
     */
    protected function getRestActionMessageStatusCode(mixed $message): ?int
    {
        if (!$message instanceof MessageInterface) {
            return null;
        }

        $code = $message->getCode();
        return $code >= 400 && $code <= 499 ? $code : null;
    }
    
    /**
     * Applies automatic, safe Cache-Control and ETag headers.
     *
     * Logic:
     *  - Only cache "GET" 200 responses.
     *  - Authenticated requests → private cache.
     *  - Unauthenticated requests → public cache.
     *  - Everything else → no-store.
     */
    protected function applyCacheHeaders(array $payload, int $code): void
    {
        $cacheConfig = $this->config->pathToArray('response.cache');
        $enabled = $cacheConfig['enable'] ?? false;
        $lifetime = $cacheConfig['lifetime'] ?? 0;
        
        // response cache is disabled
        if (!$enabled) {
            return;
        }
        
        // Default: disable caching
        $isAuthenticated = $this->identity->isLoggedIn();
        $cacheControl = 'no-store, no-cache, must-revalidate';
        $expires = '0';
        
        if ($this->request->isGet() && $code === 200 && $lifetime > 0) {
            if ($cacheConfig['etag'] ?? false) {
                $etag = hash($cacheConfig['etagAlgo'] ?? 'md5', json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '');
                $this->response->setEtag($etag);
                
                // If client's ETag matches → 304
                if ($this->request->getHeader('If-None-Match') === $etag) {
                    $this->response->setStatusCode(304, 'Not Modified');
                    return;
                }
            }
            
            $cacheControl = $isAuthenticated ? "private, max-age={$lifetime}" : "public, max-age={$lifetime}";
            $expires = gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT';
        }
        
        $this->response->setHeader('Cache-Control', $cacheControl);
        $this->response->setHeader('Expires', $expires);
        
        $this->setVaryHeaders($isAuthenticated);
    }
    
    /**
     * Sets the "Vary" HTTP header to assist caching proxies in varying responses
     * based on specific headers, particularly authentication-related headers.
     *
     * Logic:
     *  - Retrieves the default list of headers from configuration.
     *  - If the user is authenticated, adds the authorization header.
     *  - Ensures the "Vary" header is set with all relevant headers, avoiding duplicates.
     *
     * @param bool|null $isAuthenticated Indicates if the request is authenticated;
     *                                    defaults to checking the current identity.
     * @return void
     */
    public function setVaryHeaders(?bool $isAuthenticated = null): void
    {
        $isAuthenticated ??= $this->identity->isLoggedIn();
        
        // Optional: help proxies vary safely on relevant headers
        $varyHeaders = $this->config->pathToArray('response.cache.vary') ?? [];
        
        if ($isAuthenticated) {
            $varyHeaders[] = $this->identity->getOption('authorizationHeader') ?? 'X-Authorization';
        }
        
        // help proxies vary safely on auth headers
        $this->response->setHeader('Vary', implode(', ', array_unique($varyHeaders)));
    }
    
    /**
     * Gather debug context.
     */
    public function getDebugInfo(): array
    {
        return [
            'php' => [
                'version' => phpversion(),
                'sapi' => php_sapi_name(),
                'loaded_file' => php_ini_loaded_file(),
                'scanned_files' => explode(',', php_ini_scanned_files() ?: ''),
                'loaded_extensions' => get_loaded_extensions(),
                'ini' => ini_get_all(null, false),
            ],
            'phalcon' => [
                ...$this->config->pathToArray('phalcon') ?? [],
                'ini' => ini_get_all('phalcon', false)
            ],
            'phalcon-kit' => $this->config->pathToArray('core'),
            'app' => $this->config->pathToArray('app'),
            'identity' => $this->identity->getIdentity(),
            'profiler' => $this->profiler->toArray(),
            'request' => $this->request->toArray(),
            'dispatcher' => $this->dispatcher->toArray(),
            'router' => $this->router->toArray(),
            'memory' => Utils::getMemoryUsage(),
        ];
    }
    
    /**
     * Update the Dispatcher after executing the route.
     *
     * @param Dispatcher $dispatcher The Dispatcher instance.
     *
     * @return void
     */
    public function afterExecuteRoute(Dispatcher $dispatcher): void
    {
        $response = $dispatcher->getReturnedValue();
        
        // Avoid breaking default phalcon behaviour
        if ($response instanceof ResponseInterface) {
            return;
        }
        
        // Merge response into view variables
        if (is_array($response)) {
            $this->setRestViewVars($response);
        }
        
        // Return our Rest normalized response
        $dispatcher->setReturnedValue($this->setRestResponse(is_array($response) ? null : $response));
    }
}
