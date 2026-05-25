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

namespace PhalconKit\Http;

/**
 * HTTP request implementation with PhalconKit request helpers.
 *
 * The class preserves Phalcon's request API and adds CORS/preflight helpers plus
 * a diagnostic array export used by framework tooling. The CORS helpers are
 * intentionally request classifiers only; they do not apply policy, authorize
 * origins, or emit response headers.
 *
 * @see \Phalcon\Http\Request
 */
class Request extends \Phalcon\Http\Request implements RequestInterface
{
    /**
     * Return true when an Origin header targets a different origin.
     *
     * Same-origin requests with an `Origin` header are not considered CORS by
     * this helper. Policy decisions such as allowed origins should be handled by
     * middleware/controllers using this signal.
     *
     * @return bool True when the request has a cross-origin `Origin` header.
     */
    #[\Override]
    public function isCors(): bool
    {
        return !empty($this->getHeader('Origin')) && !$this->isSameOrigin();
    }
    
    /**
     * Return true when the request is a browser CORS preflight request.
     *
     * A preflight request must be cross-origin, use `OPTIONS`, and include a
     * non-empty `Access-Control-Request-Method` header. This method only
     * identifies the request shape; it does not decide whether the preflight is
     * allowed.
     *
     * @return bool True when the request is shaped like a browser CORS
     *     preflight.
     */
    #[\Override]
    public function isPreflight(): bool
    {
        return $this->isCors()
            && $this->isOptions()
            && !empty($this->getHeader('Access-Control-Request-Method'));
    }
    
    /**
     * Check whether the Origin header matches the current scheme and host.
     *
     * The comparison uses Phalcon's detected scheme and HTTP host. Deployments
     * behind proxies should make sure those values are normalized before relying
     * on this helper.
     *
     * @return bool True when `Origin` equals the request scheme and host.
     */
    #[\Override]
    public function isSameOrigin(): bool
    {
        $schemeHost = $this->getScheme() . '://' . $this->getHttpHost();
        return $this->getHeader('Origin') === $schemeHost;
    }
    
    /**
     * Export a diagnostic snapshot of request input and derived request flags.
     *
     * The result is meant for debug output and tests. It includes request
     * headers and authentication metadata, so applications should avoid logging
     * this array wholesale in production unless sensitive values are redacted.
     *
     * @return array<string, mixed> Request bodies, parameters, headers,
     *     negotiated values, origin flags, HTTP method flags, and server
     *     metadata.
     */
    #[\Override]
    public function toArray(): array
    {
        return [
            'body' => $this->getRawBody(),
            'post' => $this->getPost(),
            'get' => $this->get(),
            'put' => $this->getPut(),
            'headers' => $this->getHeaders(),
            'userAgent' => $this->getUserAgent(),
            'basicAuth' => $this->getBasicAuth(),
            'bestAccept' => $this->getBestAccept(),
            'bestCharset' => $this->getBestCharset(),
            'bestLanguage' => $this->getBestLanguage(),
            'clientAddress' => $this->getClientAddress(),
            'clientCharsets' => $this->getClientCharsets(),
            'contentType' => $this->getContentType(),
            'digestAuth' => $this->getDigestAuth(),
            'httpHost' => $this->getHttpHost(),
            'uri' => $this->getURI(),
            'serverName' => $this->getServerName(),
            'serverAddress' => $this->getServerAddress(),
            'method' => $this->getMethod(),
            'port' => $this->getPort(),
            'httpReferer' => $this->getHTTPReferer(),
            'languages' => $this->getLanguages(),
            'scheme' => $this->getScheme(),
            'isAjax' => $this->isAjax(),
            'isGet' => $this->isGet(),
            'isPost' => $this->isPost(),
            'isDelete' => $this->isDelete(),
            'isHead' => $this->isHead(),
            'isPatch' => $this->isPatch(),
            'isConnect' => $this->isConnect(),
            'isTrace' => $this->isTrace(),
            'isPut' => $this->isPut(),
            'isPurge' => $this->isPurge(),
            'isOptions' => $this->isOptions(),
            'isSoap' => $this->isSoap(),
            'isSecure' => $this->isSecure(),
            'isCors' => $this->isCors(),
            'isPreflight' => $this->isPreflight(),
            'isSameOrigin' => $this->isSameOrigin(),
            'isValidHttpMethod' => $this->isValidHttpMethod($this->getMethod()),
        ];
    }
}
