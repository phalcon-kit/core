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

namespace PhalconKit\Support;

use PhalconKit\Exception\RuntimeException;
use PhalconKit\Support\Debug\Renderer\HtmlRenderer;
use Phalcon\Support\Version as PhalconVersion;

/**
 * Customizes Phalcon's debug renderer for PhalconKit applications.
 *
 * The renderer keeps Phalcon's native exception/debug page behavior but adds
 * current-version documentation links, PhalconKit API links, and a compact
 * responsive layout for large stack traces. The output is intended for
 * development/debug environments only; production error handlers should not
 * expose this HTML to end users.
 */
class Debug extends \Phalcon\Support\Debug
{
    /**
     * Install PhalconKit's inline renderer on the native 5.16+ debug pipeline.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setRenderer(new HtmlRenderer());
    }

    /**
     * Render an uncaught exception debug page with a server-error status.
     *
     * Phalcon's native debug renderer writes the HTML page but does not
     * consistently update PHP's active response code. In browser/dev-server
     * workflows that means a fatal controller error can be shown to developers
     * while the HTTP response still reports `200 OK`. Setting the status before
     * delegating keeps debug output useful without lying to clients, upload
     * widgets, or test harnesses that rely on the transport status.
     */
    #[\Override]
    public function onUncaughtException(\Throwable $exception): bool
    {
        $this->setUncaughtExceptionStatusCode();

        return parent::onUncaughtException($exception);
    }

    /**
     * Set the transport status used by uncaught-exception debug output.
     *
     * The debug page is only reached for exceptions that escaped normal
     * controller/dispatcher handling. Treating those as `500` is the safest
     * default: expected REST validation failures should be converted to
     * framework responses before this point, while uncaught throwables represent
     * server-side failures even when their exception code happens to contain a
     * different integer.
     */
    protected function setUncaughtExceptionStatusCode(): void
    {
        if (!headers_sent()) {
            http_response_code(500);
        }
    }

    /**
     * Return version links for PhalconKit and the active Phalcon runtime.
     *
     * The Phalcon documentation link is generated from the installed major and
     * medium version so local debug pages do not drift when the framework
     * dependency is upgraded.
     *
     * @return string HTML fragment rendered in Phalcon's debug footer.
     */
    #[\Override]
    public function getVersion(): string
    {
        return $this->getRenderer()->getVersion();
    }
    
    /**
     * Rewrite Phalcon debug HTML with stable docs/API links and table markup.
     *
     * Native Phalcon debug output contains versioned API links and some table
     * header markup that is awkward to style. This method normalizes those
     * fragments, adds links for PhalconKit class names, and fails with a scoped
     * framework exception if one of the internal regex rewrites unexpectedly
     * fails.
     *
     * @throws RuntimeException When an internal debug HTML rewrite fails.
     */
    #[\Override]
    public function renderHtml(\Throwable $exception): string
    {
        $html = parent::renderHtml($exception);
        
        // --- Rewrite Phalcon URLs ---
        $phalconVersion = new PhalconVersion();
        $major = $phalconVersion->getPart(PhalconVersion::VERSION_MAJOR);
        $minor = $phalconVersion->getPart(PhalconVersion::VERSION_MEDIUM);
        
        $pattern = '#https://docs\.phalcon\.io/\d+\.\d+/(?:en/)?api/([A-Za-z_]+)#i';
        $html = preg_replace_callback(
            $pattern,
            static function (array $m) use ($major, $minor): string {
                $slug = strtolower($m[1]);
                return sprintf('https://docs.phalcon.io/%d.%d/api/%s/', $major, $minor, $slug);
            },
            $html
        );
        $html = self::requireRenderedHtml($html, 'rewriting Phalcon documentation links');

        $html = preg_replace(
            '#<thead>\s*<tr>\s*<th>Key</th>\s*</tr>\s*<tr>\s*<th>Value</th>\s*</tr>\s*</thead>#',
            '<thead><tr><th class="key" scope="col">Key</th><th scope="col">Value</th></tr></thead>',
            $html
        );
        $html = self::requireRenderedHtml($html, 'normalizing key/value debug table headers');
        $html = preg_replace(
            '#<thead>\s*<tr>\s*<th(?:\s+class=[\'"]key[\'"])?(?:\s+scope=[\'"]col[\'"])?>Key</th>\s*<th(?:\s+scope=[\'"]col[\'"])?>Value</th>\s*</tr>\s*</thead>#',
            '<thead><tr><th class="key" scope="col">Key</th><th scope="col">Value</th></tr></thead>',
            $html
        );
        $html = self::requireRenderedHtml($html, 'normalizing key/value debug table headers');

        $html = preg_replace(
            '~<thead>\s*<tr>\s*<th>\#</th>\s*</tr>\s*<tr>\s*<th>Path</th>\s*</tr>\s*</thead>~',
            '<thead><tr><th class="number" scope="col">#</th><th scope="col">Path</th></tr></thead>',
            $html
        );
        $html = self::requireRenderedHtml($html, 'normalizing included-file debug table headers');
        $html = preg_replace(
            '~<thead>\s*<tr>\s*<th(?:\s+class=[\'"]number[\'"])?(?:\s+scope=[\'"]col[\'"])?>\#</th>\s*<th(?:\s+scope=[\'"]col[\'"])?>Path</th>\s*</tr>\s*</thead>~',
            '<thead><tr><th class="number" scope="col">#</th><th scope="col">Path</th></tr></thead>',
            $html
        );
        $html = self::requireRenderedHtml($html, 'normalizing included-file debug table headers');

        $html = preg_replace(
            '#<thead>\s*<tr>\s*<th>Memory</th>\s*</tr>\s*<tr>\s*<th></th>\s*</tr>\s*</thead>#',
            '<thead><tr><th class="key" scope="col">Memory</th><th scope="col">Value</th></tr></thead>',
            $html
        );
        $html = self::requireRenderedHtml($html, 'normalizing memory debug table headers');
        $html = preg_replace(
            '#<thead>\s*<tr>\s*<th\s+class=[\'"]key[\'"](?:\s+scope=[\'"]col[\'"])?>Memory</th>\s*<th(?:\s+scope=[\'"]col[\'"])?>Value</th>\s*</tr>\s*</thead>#',
            '<thead><tr><th class="key" scope="col">Memory</th><th scope="col">Value</th></tr></thead>',
            $html
        );
        $html = self::requireRenderedHtml($html, 'normalizing memory debug table headers');
        $html = preg_replace(
            '#<table([^>]*)class=(["\'])(?![^"\']*\bkv-grid\b)([^"\']*(?:\bgrid\b|\bsuperglobal-detail\b)[^"\']*)\2([^>]*)>'
                . '\s*<thead><tr><th class="key" scope="col">(Key|Memory)</th><th scope="col">Value</th></tr></thead>#',
            '<table$1class=$2$3 kv-grid$2$4>'
                . '<colgroup><col class="key-col"><col class="value-col"></colgroup>'
                . '<thead><tr><th class="key" scope="col">$5</th><th scope="col">Value</th></tr></thead>',
            $html
        );
        $html = self::requireRenderedHtml($html, 'marking key/value debug tables');
        $html = preg_replace(
            '~<table([^>]*)class=(["\'])(?![^"\']*\bfiles-grid\b)([^"\']*\bgrid\b[^"\']*)\2([^>]*)>'
                . '\s*<thead><tr><th class="number" scope="col">\#</th><th scope="col">Path</th></tr></thead>~',
            '<table$1class=$2$3 files-grid$2$4>'
                . '<colgroup><col class="number-col"><col></colgroup>'
                . '<thead><tr><th class="number" scope="col">#</th><th scope="col">Path</th></tr></thead>',
            $html
        );
        $html = self::requireRenderedHtml($html, 'marking included-file debug tables');
        $html = str_replace(
            [
                "data-action='expand-all'>Expand all",
                "data-action='collapse-all'>Collapse all",
            ],
            [
                "data-action='expand-all'>Expand code",
                "data-action='collapse-all'>Collapse code",
            ],
            $html
        );
        
        // --- Add Phalcon Kit class links ---
        $html = preg_replace_callback(
            '#(?<!href=")(PhalconKit\\\\[A-Za-z0-9_\\\\]+)#',
            static function (array $m): string {
                $class = $m[1];
                $path = str_replace('\\', '/', $class);
                $url = "https://github.com/phalcon-kit/docs/tree/main/docs/api/classes/{$path}.md";
                return sprintf(
                    '<a target="_new" href="%s">%s</a>',
                    htmlspecialchars($url, ENT_QUOTES),
                    htmlspecialchars($class, ENT_QUOTES)
                );
            },
            $html
        );
        $html = self::requireRenderedHtml($html, 'linking PhalconKit API classes');
        $html = self::normalizeBacktraceFrames($html);
        
        return $html;
    }

    /**
     * Keep backtrace metadata visible while only the source block expands.
     *
     * Phalcon 5.16+ renders each frame as a `<details>` element, which hides the
     * file and line number when a frame is collapsed. PhalconKit keeps the frame
     * header and file path visible, then moves source context into its own
     * expandable region. When the source file is readable and reasonably sized,
     * a hidden full-file template is also embedded for the inline toggle button.
     *
     * @throws RuntimeException When an internal debug HTML rewrite fails.
     */
    private static function normalizeBacktraceFrames(string $html): string
    {
        $offset = 0;
        $normalized = '';
        $openNeedle = "<details class='frame";
        $closeNeedle = '</details>';

        while (($start = strpos($html, $openNeedle, $offset)) !== false) {
            $openEnd = strpos($html, '>', $start);

            if ($openEnd === false) {
                break;
            }

            $closeStart = strpos($html, $closeNeedle, $openEnd + 1);

            if ($closeStart === false) {
                break;
            }

            $closeEnd = $closeStart + strlen($closeNeedle);
            $openingTag = substr($html, $start, $openEnd - $start + 1);
            $body = substr($html, $openEnd + 1, $closeStart - $openEnd - 1);
            $originalFrame = substr($html, $start, $closeEnd - $start);

            $normalized .= substr($html, $offset, $start - $offset);
            $normalized .= self::normalizeBacktraceFrame($openingTag, $body, $originalFrame);
            $offset = $closeEnd;
        }

        if ($offset === 0) {
            return $html;
        }

        return $normalized . substr($html, $offset);
    }

    /**
     * Normalize one native debug frame after the frame boundary is known.
     *
     * Frame extraction intentionally avoids a document-wide regex so large
     * backtraces cannot exhaust PCRE backtracking before the per-frame rewrite
     * runs. If Phalcon changes the opening tag shape, the original frame is
     * preserved instead of dropping debug output.
     *
     * @throws RuntimeException When an internal frame rewrite fails.
     */
    private static function normalizeBacktraceFrame(string $openingTag, string $body, string $originalFrame): string
    {
        if (!preg_match("#^<details class='frame(?P<class>[^']*)'(?P<open>\\s+open)?\\s*>$#s", $openingTag, $match)) {
            return $originalFrame;
        }

        if (!preg_match(
            "#\\s*<summary>\\s*(<div class='frame-head'>.*?</div>)\\s*</summary>#s",
            $body,
            $summary
        )) {
            return $originalFrame;
        }

        $head = preg_replace("#\\s*<span class='chev'>.*?</span>#s", '', $summary[1]);
        $head = self::requireRenderedHtml($head, 'removing the frame-level backtrace chevron');
        $body = str_replace($summary[0], '', $body);

        $fileBlock = '';
        $file = null;
        $line = null;

        if (preg_match("#\\s*(<div class='frame-file'[^>]*>.*?</div>)#s", $body, $fileMatch)) {
            $fileBlock = $fileMatch[1];
            $body = str_replace($fileMatch[0], '', $body);
            $file = self::readHtmlAttribute($fileBlock, 'data-file');
            $lineValue = self::readHtmlAttribute($fileBlock, 'data-line');
            $line = is_numeric($lineValue) ? (int) $lineValue : null;
        }

        $codeBlock = '';

        if (preg_match("#\\s*(<div class='code'>.*?</div>)#s", $body, $codeMatch)) {
            $codeBlock = $codeMatch[1];
            $body = str_replace($codeMatch[0], '', $body);
        }

        $isCodeOpen = ($match['open'] ?? '') !== '';
        $classes = trim(
            'frame'
            . $match['class']
            . ($codeBlock !== '' ? ' has-source' : ' no-source')
            . ($codeBlock !== '' && $isCodeOpen ? ' is-code-open' : '')
        );

        if ($codeBlock !== '') {
            $head = self::makeBacktraceFrameHeadToggle($head, $isCodeOpen);
        }

        $frame = "\n        <article class='" . htmlspecialchars($classes, ENT_QUOTES) . "'>\n            {$head}";

        if ($fileBlock !== '') {
            $frame .= "\n            {$fileBlock}";
        }

        if ($codeBlock !== '') {
            $fullFileTemplate = self::buildFullFileSourceTemplate($file, $line);
            $fullFileButton = $fullFileTemplate !== ''
                ? "\n                    <button class='btn code-btn' "
                    . "data-action='toggle-full-file'>Show full file</button>"
                : '';

            $hidden = $isCodeOpen ? '' : ' hidden';
            $frame .= "\n            <div class='frame-code-body'{$hidden}>"
                . "\n                <div class='code-shell'>"
                . "\n                    {$codeBlock}"
                . "\n                    <div class='code-actions'>"
                . "\n                        <button class='btn code-btn' data-action='focus-line'>Focus line</button>"
                . str_replace("\n                    ", "\n                        ", $fullFileButton)
                . "\n                    </div>"
                . "\n                </div>"
                . $fullFileTemplate
                . "\n            </div>";
        }

        $extra = trim($body);

        if ($extra !== '') {
            $frame .= "\n            <div class='frame-extra'>{$extra}</div>";
        }

        return $frame . "\n        </article>";
    }

    /**
     * Turn the native frame header into the source-context toggle.
     *
     * The path row remains outside the toggle so file and line metadata stay
     * visible even when source context is collapsed.
     *
     * @throws RuntimeException When the rewrite failed.
     */
    private static function makeBacktraceFrameHeadToggle(string $head, bool $isOpen): string
    {
        $expanded = $isOpen ? 'true' : 'false';
        $head = trim($head);
        $head = preg_replace(
            "#^<div class='frame-head'>#",
            "<button type='button' class='frame-head' "
                . "data-action='toggle-frame-code' aria-expanded='{$expanded}'>",
            $head
        );
        $head = self::requireRenderedHtml($head, 'making backtrace frame header clickable');
        $head = preg_replace(
            '#</div>$#',
            "<span class='chev' aria-hidden='true'>&#9656;</span></button>",
            $head
        );

        return self::requireRenderedHtml($head, 'adding the backtrace frame header chevron');
    }

    /**
     * Read an HTML attribute value from a native debug fragment.
     */
    private static function readHtmlAttribute(string $html, string $attribute): ?string
    {
        $quoted = preg_quote($attribute, '#');

        if (!preg_match("#\\s{$quoted}=(['\"])(.*?)\\1#s", $html, $match)) {
            return null;
        }

        return html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Build a hidden full-file source table for the inline source toggle.
     */
    private static function buildFullFileSourceTemplate(?string $file, ?int $line): string
    {
        if ($file === null || !is_file($file) || !is_readable($file)) {
            return '';
        }

        $size = filesize($file);

        if ($size === false || $size > 1_000_000) {
            return '';
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        if (!is_array($lines)) {
            return '';
        }

        $rows = [];

        foreach ($lines as $index => $sourceLine) {
            $lineNumber = $index + 1;
            $class = $lineNumber === $line ? " class='hl'" : '';
            $rows[] = sprintf(
                "<tr%s><td class='ln'>%d</td><td class='src'>%s</td></tr>",
                $class,
                $lineNumber,
                htmlspecialchars($sourceLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        return "\n                <template class='code-full-template'><table>"
            . implode('', $rows)
            . '</table></template>';
    }

    /**
     * Require an internal debug rewrite to return rendered HTML.
     *
     * `preg_replace()` and `preg_replace_callback()` return null on regex
     * failures and can return arrays when the subject is an array. This class
     * always rewrites a string subject, so anything other than a string means
     * PhalconKit's debug renderer is misconfigured or has drifted from the
     * native Phalcon output shape.
     *
     * @throws RuntimeException When the rewrite failed.
     */
    private static function requireRenderedHtml(string|array|null $html, string $operation): string
    {
        if (!is_string($html)) {
            throw new RuntimeException(sprintf(
                'Could not render PhalconKit debug HTML while %s.',
                $operation
            ));
        }

        return $html;
    }

    /**
     * Return the CSS injected into Phalcon's debug page.
     *
     * The stylesheet intentionally avoids external assets so debug pages remain
     * useful when the asset pipeline, router, or public document root is broken.
     */
    #[\Override]
    public function getCssSources(): string
    {
        $renderer = $this->getRenderer();

        if ($renderer instanceof HtmlRenderer) {
            return $renderer->getCssSources('');
        }

        return <<<'STYLE'
<style>
:root{--bg-main:#ffffff;--bg-panel:#ffffff;--bg-alt:#f5f5f5;--bg-code:#0a0a0a;--text-main:#000000;--text-dim:#555555;--text-invert:#ffffff;--border:#000000;--border-light:#d0d0d0;--mono:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;--sans:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;--panel-pad-x:24px;--panel-pad-y:20px;--code-pad-x:16px;--code-pad-y:14px}
*{border-radius:0!important;box-sizing:border-box}
html,body{width:100%;max-width:100%;padding:0;margin:0;display:flex;flex-direction:column;align-items:center;overflow-x:hidden;overflow-anchor:none;scroll-behavior:smooth;background:var(--bg-main);color:var(--text-main);font-family:var(--sans);font-size:15px;line-height:1.4}
body::-webkit-scrollbar{width:8px}
body::-webkit-scrollbar-track{background:#fff}
body::-webkit-scrollbar-thumb{background:#000}
::selection{background:#000;color:#fff}
::-moz-selection{background:#000;color:#fff}
a,body,h1,html{color:var(--text-main)}
a:hover{text-decoration:none}
h1{margin:0 0 .5rem;padding-bottom:6px;border-bottom:1px solid var(--border);font-size:1.2rem;font-weight:600}
.error-info,.error-main{width:100%;max-width:980px;background:var(--bg-panel);box-shadow:0 20px 60px rgba(0,0,0,.15)}
.error-main{margin:2rem auto 0;padding:16px 20px;border:1px solid var(--border);border-bottom:none}
.error-info{margin:0 auto 2rem;border:1px solid var(--border);overflow:hidden}
.error-main .error-file{display:block;margin-top:.5rem;font-size:.8rem;opacity:.8}
.error-file,.version{color:var(--text-dim);font-family:var(--mono)}
.error-file{margin:6px 0 12px;font-size:.75rem;word-break:break-all}
.error-class,.error-function{font-weight:600;color:var(--text-main)}
.error-function{display:inline-block;margin-bottom:4px}
#tabs{width:100%;min-width:0;overflow:hidden;background:var(--bg-panel)}
#tabs>ul{display:none;margin:0;padding:0;list-style:none;justify-content:center;flex-wrap:nowrap;overflow-x:auto;border-bottom:1px solid var(--border);background:var(--bg-panel);-webkit-overflow-scrolling:touch}
#tabs.debug-js>ul{display:flex}
#tabs>ul::-webkit-scrollbar,pre.prettyprint::-webkit-scrollbar{width:6px;height:6px}
#tabs>ul::-webkit-scrollbar-track,pre.prettyprint::-webkit-scrollbar-track{background:#000}
#tabs>ul::-webkit-scrollbar-thumb,pre.prettyprint::-webkit-scrollbar-thumb{background:#fff}
#tabs>ul>li{flex:1 1 auto;min-width:120px;text-align:center;border-right:1px solid var(--border);background:var(--bg-panel);font-size:.8rem}
#tabs>ul>li:first-child{border-left:0}
#tabs>ul>li:last-child{border-right:0}
#tabs>ul>li>a{display:block;padding:10px 0;color:var(--text-main);text-decoration:none;user-select:none;white-space:nowrap}
#tabs>ul>li>a:hover{background:var(--bg-alt)}
#tabs>ul>li.active>a{background:var(--text-main);color:var(--text-invert)}
#tabs>div[id^=error-tabs-],#tabs>div[id]{display:block;width:100%;max-width:100%;min-width:0;padding:var(--panel-pad-y) var(--panel-pad-x) 22px;overflow-x:hidden;overflow-y:visible;border-top:1px solid var(--border-light);background:var(--bg-panel);font-size:.9rem;line-height:1.5;word-wrap:break-word;overflow-wrap:break-word}
#tabs>div[id]:first-of-type{border-top:0}
#tabs.debug-js>div[id^=error-tabs-],#tabs.debug-js>div[id]{display:none;border-top:0}
#tabs.debug-js>div.active{display:block}
#tabs>div[id]::before{display:block;margin:0 0 16px;padding-bottom:8px;border-bottom:1px solid var(--border-light);font-size:.95rem;font-weight:600;line-height:1.2;color:var(--text-main)}
#tabs.debug-js>div[id]::before{display:none}
#backtrace::before{content:"Backtrace"}
#request::before{content:"Request"}
#server::before{content:"Server"}
#files::before{content:"Included Files"}
#memory::before{content:"Memory"}
#tabs hr{height:1px;margin:10px 0;border:0;border-top:1px solid var(--border-light)}
#error-tabs-1 table,#backtrace table,.superglobal-detail{width:100%;border-collapse:collapse;border-spacing:0;table-layout:fixed;text-align:left!important;font-size:.82rem;word-break:break-word}
#error-tabs-1 table,#backtrace table{font-size:.9rem;line-height:1.5}
#error-tabs-1 table td,#backtrace table td{padding:16px 0 18px;border-top:1px solid var(--border-light);text-align:left;vertical-align:top;min-width:0;max-width:0;overflow:hidden;color:#000}
#error-tabs-1 table tr:first-child td,#backtrace table tr:first-child td{border-top:0}
.error-number{width:50px!important;max-width:50px!important;padding-right:14px!important;color:var(--text-dim);font-family:var(--mono);font-size:.8rem;text-align:right!important}
.error-function{font-size:.95rem;line-height:1.35}
.superglobal-detail{border:1px solid var(--border-light);background:#fff;color:#000}
.superglobal-detail th,.superglobal-detail td{padding:8px 10px;border:1px solid var(--border-light);text-align:left;vertical-align:top;line-height:1.45;color:#000}
.superglobal-detail th{background:#eee;font-weight:600;white-space:nowrap}
.superglobal-detail th.key,.superglobal-detail td.key,#memory th:first-child,#memory td:first-child{width:220px;font-weight:600;white-space:nowrap}
.superglobal-detail td{background:#fff}
#files th.number,#files td:first-child{width:64px;font-family:var(--mono);text-align:right!important;white-space:nowrap}
#files th:nth-child(2),#files td:nth-child(2){text-align:left;word-break:break-all}
pre.prettyprint{display:block;inline-size:100%;max-inline-size:100%;width:100%;max-width:100%;min-width:0;contain:inline-size;margin:14px 0 18px;padding:var(--code-pad-y) var(--code-pad-x) calc(var(--code-pad-y) + 4px);overflow-x:auto;overflow-y:auto;max-height:220px;background:var(--bg-code);color:var(--text-invert);border:1px solid var(--border);font-family:var(--mono);font-size:.78rem;line-height:1.5;white-space:pre;tab-size:4;position:relative}
#tabs:not(.debug-js) pre.prettyprint{display:none}
pre.prettyprint .code-ellipsis{display:block;text-align:center;color:#888;background:#000;font-style:italic;letter-spacing:2px;user-select:none}
pre.prettyprint .code-line{display:inline-flex;min-width:100%;width:auto;max-width:none;padding-right:var(--code-pad-x);background:#000;color:#fff;scroll-margin-top:40px;flex-direction:row;align-items:flex-start;white-space:pre}
pre.prettyprint .code-line.hl,.expand-toggle:hover{background:#4f4f4f}
pre.prettyprint .code-line.hl .ln{color:#000}
pre.prettyprint .ln{display:inline-block;width:3em;min-width:3em;padding-right:1em;color:#777;text-align:right;user-select:none;flex-shrink:0}
pre.prettyprint ::selection{background:#fff;color:#000}
pre.prettyprint ::-moz-selection{background:#fff;color:#000}
pre.prettyprint .highlight,pre.prettyprint mark{background:#fff;color:#000;padding:0 2px}
body :not(pre) mark{background:#000;color:#fff;padding:0 2px}
.expand-toggle{display:block;position:sticky;right:0;bottom:12px;z-index:999;max-width:calc(100% - 8px);margin:14px 0 0 auto;padding:5px 9px;background:#000;color:#fff;border:1px solid #fff;font-family:var(--mono);font-size:.75rem;white-space:nowrap;cursor:pointer;transition:background .15s}
.version{position:fixed;right:12px;bottom:12px;z-index:100;padding:3px 6px;border:1px solid var(--border-light);background:var(--bg-panel);font-size:.8rem;line-height:1.3;opacity:.9;white-space:nowrap}
.version a{color:var(--text-main);text-decoration:underline}
.version a:hover{text-decoration:none}
</style>
STYLE;
    }
    
    /**
     * Return the JavaScript injected into Phalcon's debug page.
     *
     * The script progressively enhances the native debug output: tabs are only
     * activated when the expected markup exists, and source previews collapse
     * large files around the highlighted line without requiring external
     * dependencies.
     */
    #[\Override]
    public function getJsSources(): string
    {
        $renderer = $this->getRenderer();

        if ($renderer instanceof HtmlRenderer) {
            return $renderer->getJsSources('');
        }

        return <<<'SCRIPT'
<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabs = document.getElementById("tabs");

    if (tabs) {
        const tabLinks = tabs.querySelectorAll('ul > li > a[href^="#"]');
        const panes = Array.from(tabLinks)
            .map((link) => document.querySelector(link.getAttribute("href")))
            .filter(Boolean);

        if (tabLinks.length > 0) {
            tabs.classList.add("debug-js");

            function activateTab(target) {
                for (const link of tabLinks) {
                    const item = link.parentElement;
                    item.classList.toggle("active", link.getAttribute("href") === target);
                }

                for (const pane of panes) {
                    pane.classList.toggle("active", `#${pane.id}` === target);
                }
            }

            for (const link of tabLinks) {
                link.addEventListener("click", (event) => {
                    event.preventDefault();
                    activateTab(link.getAttribute("href"));
                });
            }

            activateTab(tabLinks[0].getAttribute("href"));
        }
    }

    for (const pre of document.querySelectorAll("pre.prettyprint")) {
        const className = pre.className || "";
        const highlight = className.match(/highlight:(\d+):(\d+)/);
        const highlightLine = highlight ? Number.parseInt(highlight[2], 10) : null;
        const originalText = pre.textContent.replaceAll("\r\n", "\n");
        const lines = originalText.split("\n");
        const lineCount = lines.length;

        function escapeLine(value) {
            return value
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll("\t", "    ");
        }

        function renderLines(firstLine, lastLine) {
            const html = [];

            for (let lineNumber = firstLine; lineNumber <= lastLine; lineNumber++) {
                const highlighted = lineNumber === highlightLine;
                const line = escapeLine(lines[lineNumber - 1] || " ");

                html.push(`<span class="code-line${highlighted ? " hl" : ""}">${line}</span>`);
            }

            return html;
        }

        function scrollToHighlight() {
            const highlighted = pre.querySelector(".code-line.hl");

            if (highlighted) {
                requestAnimationFrame(() => {
                    pre.scrollTo({
                        top: highlighted.offsetTop - pre.clientHeight / 2,
                        behavior: "instant",
                    });
                });
            }
        }

        function addToggle(label, callback) {
            const button = document.createElement("button");

            button.className = "expand-toggle";
            button.textContent = label;
            button.addEventListener("click", (event) => {
                event.preventDefault();
                event.stopPropagation();
                callback();
            });

            pre.appendChild(button);
        }

        function renderPreview() {
            const firstLine = highlightLine ? Math.max(1, highlightLine - 7) : 1;
            const lastLine = highlightLine ? Math.min(lineCount, highlightLine + 5) : lineCount;
            const html = renderLines(firstLine, lastLine);

            if (firstLine > 1) {
                html.unshift('<span class="code-ellipsis">…</span>');
            }

            if (lastLine < lineCount) {
                html.push('<span class="code-ellipsis">…</span>');
            }

            pre.innerHTML = html.join("\n");
            addToggle("Show full file", renderFullFile);
            scrollToHighlight();
        }

        function renderFullFile() {
            pre.innerHTML = renderLines(1, lineCount).join("\n");
            addToggle("Collapse", renderPreview);
            scrollToHighlight();
        }

        renderPreview();
    }
});
</script>
SCRIPT;
    }
}
