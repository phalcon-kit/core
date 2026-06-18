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

namespace PhalconKit\Mvc;

use PhalconKit\Support\Helper;
use PhalconKit\Support\Slug;

/**
 * MVC view wrapper with PhalconKit path normalization and optional minification.
 *
 * When a direct controller/action view path is not present, controller and
 * action names are converted from camelCase to slug form before delegating to
 * Phalcon. `getContent()` can also perform lightweight HTML output minification
 * for applications that opt in through `setMinify()`.
 *
 * @see https://docs.phalcon.io/5.15/views/
 */
class View extends \Phalcon\Mvc\View
{
    /**
     * Whether `getContent()` should minify rendered HTML by default.
     *
     * The flag is intentionally view-local so applications can opt in through
     * configuration without changing Phalcon's global rendering behavior.
     */
    private bool $minify = false;
    
    /**
     * Return whether response content should be minified by default.
     *
     * @return bool True when rendered content should be minified unless a
     *     per-call override is supplied to `getContent()`.
     */
    public function getMinify(): bool
    {
        return $this->minify;
    }
    
    /**
     * Enable or disable response content minification by default.
     *
     * @param bool $minify True to minify rendered content returned by
     *     `getContent()` unless the call overrides the behavior.
     */
    public function setMinify(bool $minify): void
    {
        $this->minify = $minify;
    }
    
    /**
     * Render a view, falling back to slugged controller/action paths.
     *
     * @param string $controllerName Controller name selected by the dispatcher.
     * @param string $actionName Action name selected by the dispatcher.
     * @param array<array-key, mixed> $params View parameters.
     *
     * @return static|false Native Phalcon render result.
     */
    #[\Override]
    public function render(string $controllerName, string $actionName, array $params = []): static|false
    {
        if (!$this->has($controllerName . (empty($actionName) ? null : '/' . $actionName))) {
            $controllerName = Slug::generate(Helper::uncamelize($controllerName));
            $actionName = Slug::generate(Helper::uncamelize($actionName));
        }
        return parent::render($controllerName, $actionName, $params);
    }
    
    /**
     * Render a view to a string, falling back to slugged paths when needed.
     *
     * @param string $controllerName Controller name selected by the dispatcher.
     * @param string $actionName Action name selected by the dispatcher.
     * @param array<array-key, mixed> $params View parameters.
     * @param mixed $configCallback Optional native Phalcon render callback.
     *
     * @return string
     */
    #[\Override]
    public function getRender(string $controllerName, string $actionName, array $params = [], $configCallback = null): string
    {
        if (!$this->has($controllerName . (empty($actionName) ? null : '/' . $actionName))) {
            $controllerName = Slug::generate(Helper::uncamelize($controllerName));
            $actionName = Slug::generate(Helper::uncamelize($actionName));
        }
        return parent::getRender($controllerName, $actionName, $params, $configCallback);
    }
    
    /**
     * Return rendered output, optionally applying lightweight minification.
     *
     * The minifier removes normal HTML comments, single-line JavaScript-style
     * comments, repeated whitespace, and line breaks. It is intentionally simple
     * and should be used for conventional rendered views, not as a full HTML,
     * CSS, or JavaScript optimizer.
     *
     * @param bool|null $minify Override the default minification flag for this
     *     call. Null uses `getMinify()`.
     *
     * @return string Rendered response content.
     */
    #[\Override]
    public function getContent(?bool $minify = null): string
    {
        // Normalize content from parent to a string
        $content = parent::getContent();
        
        // Determine if minification should apply
        $shouldMinify = $minify ?? $this->getMinify();
        if (!$shouldMinify || $content === '') {
            return $content;
        }
    
        $result = preg_replace([
            '/<!--(?!\[if).*?-->/s', // remove HTML comments except conditional ones like <!--[if IE]>
            '/(?<!\S)\/\/[^\r\n]*/', // remove JS-style single-line comments
            '/\s{2,}/u', // collapse multiple spaces into one
            '/\r?\n/', // remove newlines
        ], ['', '', ' ', ''], $content);
        
        // Normalize and return
        return trim(is_array($result) ? implode('', $result) : (string) $result);
    }
}
