<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For full copyright and license information,
 * please view the LICENSE.txt file distributed with this source code.
 */

namespace PhalconKit\Mvc\Controller\Traits;

use Phalcon\Filter\Exception as FilterException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;

trait Params
{
    use AbstractParams;
    use AbstractInjectable;
    
    /** @var array<array-key, mixed>|null Cached raw request parameters */
    protected ?array $rawParams = null;
    
    /** @var array<string, array|string> Default filters applied to params */
    protected array $defaultFilters = [];
    
    /**
     * Retrieve a specific parameter value by key.
     *
     * @param string $key
     * @param array|string|null $filters
     * @param mixed|null $default
     * @param array|null $params
     * @return mixed
     * @throws FilterException When request parameter filtering fails.
     */
    public function getParam(string $key, array|string|null $filters = null, mixed $default = null, ?array $params = null): mixed
    {
        $params ??= $this->getAllParams();
        
        if (array_key_exists($key, $params)) {
            return $this->filter->sanitize($params[$key], $filters ?? []);
        }
        
        return $this->dispatcher->getParam($key, $filters ?? [], $default);
    }
    
    /**
     * Check if a given key exists in the parameter array.
     *
     * @param string $key
     * @param array|null $params
     * @param bool $cached
     * @return bool
     */
    public function hasParam(string $key, ?array $params = null, bool $cached = true): bool
    {
        $params ??= $this->getRawParams($cached);
        return array_key_exists($key, $params);
    }
    
    /**
     * Retrieve specific or all request parameters.
     *
     * Usage examples:
     * - getParams() -> all params
     * - getParams(['email', 'password']) -> only those keys
     * - getParams(['email' => [Filter::TRIM], 'password']) -> filtered subset
     *
     * @param array|null $fields Keys or key=>filters to extract.
     * @param bool $cached Whether to reuse cached raw parameters.
     * @param bool $deep Whether to apply deep sanitization.
     *
     * @return array<array-key, mixed>
     * @throws FilterException When request parameter filtering fails.
     */
    public function getParams(?array $fields = null, bool $cached = true, bool $deep = true): array
    {
        // return all parameters if no specific parameters are requested
        if (is_null($fields)) {
            return $this->getAllParams(null, $cached, $deep);
        }
        
        // prepare filters from the params array
        $filters = array_filter($fields, function ($key) {
            return !is_int($key);
        }, ARRAY_FILTER_USE_KEY);
        
        $allParams = $this->getAllParams($filters, $cached, $deep);
        
        // build the result subset
        $params = [];
        foreach ($fields as $key => $value) {
            $name = is_int($key) ? $value : $key;
            $params[$name] = $allParams[$name] ?? null;
        }
        
        return $params;
    }
    
    /**
     * Retrieve all request parameters, optionally applying filters and caching results.
     *
     * @param array|null $filters Temporary filters to apply.
     * @param bool $cached Whether to reuse previously loaded parameters.
     * @param bool $deep Whether to apply filters recursively.
     *
     * @return array<array-key, mixed>
     * @throws FilterException When request parameter filtering fails.
     */
    public function getAllParams(?array $filters = null, bool $cached = true, bool $deep = true): array
    {
        // Merge default + persistent + temporary filters
        $mergedFilters = array_merge($this->getDefaultFilters(), $filters ?? []);
        
        if ($cached && isset($this->rawParams)) {
            return $this->applyFilters($this->rawParams, $mergedFilters, $deep);
        }
        
        $this->rawParams = $this->collectRequestParams();
        
        return $this->applyFilters($this->rawParams, $mergedFilters, $deep);
    }
    
    /**
     * Collect parameters from one request source based on the HTTP method.
     *
     * Body methods prefer an explicitly JSON request body and otherwise use the
     * matching form body. Query parameters are intentionally not merged into
     * body payloads so save endpoints cannot accidentally persist route/query
     * controls such as `with`, `filters`, or `order`.
     *
     * @return array<array-key, mixed>
     */
    private function collectRequestParams(): array
    {
        $params = match (true) {
            $this->request->isPost() => $this->collectBodyParams($this->request->getPost()),
            $this->request->isPatch() => $this->collectBodyParams($this->request->getPatch()),
            $this->request->isPut() => $this->collectBodyParams($this->request->getPut()),
            default => $this->normalizeRequestParams($this->request->getQuery()),
        };
        
        unset($params['_url']); // remove Phalcon's default _url param
        
        return $params;
    }

    /**
     * Collect body parameters from JSON or the method-specific form payload.
     *
     * @param mixed $formParams Method-specific form payload from Phalcon.
     *
     * @return array<array-key, mixed>
     */
    private function collectBodyParams(mixed $formParams): array
    {
        return $this->collectJsonRequestParams()
            ?? $this->normalizeRequestParams($formParams);
    }

    /**
     * Collect JSON request parameters when a body request explicitly sends JSON.
     *
     * @return array<array-key, mixed>|null
     */
    private function collectJsonRequestParams(): ?array
    {
        if (!$this->hasJsonContentType()) {
            return null;
        }

        $params = $this->request->getJsonRawBody(true);

        return is_array($params) ? $params : null;
    }

    /**
     * Return true for standard JSON and vendor JSON request content types.
     */
    private function hasJsonContentType(): bool
    {
        $contentType = strtolower((string)$this->request->getContentType());
        $contentType = trim(explode(';', $contentType, 2)[0]);

        return $contentType === 'application/json'
            || str_ends_with($contentType, '+json');
    }

    /**
     * Normalize Phalcon request accessor output into a parameter array.
     *
     * @return array<array-key, mixed>
     */
    private function normalizeRequestParams(mixed $params): array
    {
        return is_array($params) ? $params : [];
    }
    
    /**
     * Apply filters to parameters (recursively if $deep is true).
     *
     * @param array<array-key, mixed> $params
     * @param array<string, array|string> $filters
     * @param bool $deep
     *
     * @return array<array-key, mixed>
     * @throws FilterException When request parameter filtering fails.
     */
    public function applyFilters(array $params, array $filters, bool $deep = true): array
    {
        foreach ($filters as $key => $sanitizers) {
            if (array_key_exists($key, $params)) {
                $params[$key] = $deep
                    ? $this->deepSanitize($params[$key], (array)$sanitizers)
                    : $this->filter->sanitize($params[$key], (array)$sanitizers);
            }
        }
        
        return $params;
    }
    
    /**
     * Recursively sanitize nested arrays.
     *
     * @param mixed $value
     * @param array|string $filters
     * @return mixed
     * @throws FilterException When request parameter filtering fails.
     */
    private function deepSanitize(mixed $value, array|string $filters): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->deepSanitize($v, $filters);
            }
            return $value;
        }
        
        return $this->filter->sanitize($value, $filters);
    }
    
    /**
     * Sets default filters, replacing any previously defined.
     *
     * @param array<string, array|string> $filters
     */
    public function setDefaultFilters(array $filters): static
    {
        $this->defaultFilters = $filters;
        return $this;
    }
    
    /**
     * Adds or merges new default filters to existing ones.
     *
     * @param array<string, array|string> $filters
     */
    public function addDefaultFilters(array $filters): static
    {
        $this->defaultFilters = array_merge($this->defaultFilters, $filters);
        return $this;
    }
    
    /**
     * Remove one or many default filters by key.
     *
     * @param string|array<int, string> $keys
     */
    public function removeFilters(string|array $keys): static
    {
        foreach ((array)$keys as $key) {
            unset($this->defaultFilters[$key]);
        }
        return $this;
    }
    
    /**
     * Clears all default filters.
     */
    public function clearDefaultFilters(): static
    {
        $this->defaultFilters = [];
        return $this;
    }
    
    /**
     * Get currently active default filters.
     *
     * @return array<string, array|string>
     */
    public function getDefaultFilters(): array
    {
        return $this->defaultFilters;
    }
    
    /**
     * Retrieves the raw parameters from the request. If caching is enabled, it returns the cached parameters.
     *
     * @param bool $cached Determines whether to use cached parameters. Defaults to true.
     * @return array<array-key, mixed> The raw request parameters.
     */
    public function getRawParams(bool $cached = true): array
    {
        if ($cached && isset($this->rawParams)) {
            return $this->rawParams;
        }
        
        return $this->rawParams = $this->collectRequestParams();
    }
}
