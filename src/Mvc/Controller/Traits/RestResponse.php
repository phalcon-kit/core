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
use Phalcon\Mvc\Dispatcher;
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
     * Set the REST response error
     *
     * @param int $code The HTTP status code (default: 400)
     * @param ?string $status The status message (default: 'Bad Request')
     * @param mixed $response The response body (default: null)
     * @return ResponseInterface The REST response object
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
