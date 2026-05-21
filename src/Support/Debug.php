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

use PhalconKit\Support\Version as PhalconKitVersion;
use Phalcon\Support\Version as PhalconVersion;

/**
 * Provides debug capabilities to Phalcon Kit applications
 */
class Debug extends \Phalcon\Support\Debug
{
    /**
     * Returns the version information for Phalcon Kit and Phalcon Framework.
     *
     * @return string The version information as HTML string.
     */
    #[\Override]
    public function getVersion(): string
    {
        $phalconKit = new PhalconKitVersion();
        $phalcon = new PhalconVersion();
        
        return sprintf(
            '<div class="version">Phalcon Kit <a href="https://github.com/phalcon-kit/core" target="_new">%s</a> | Phalcon Framework <a href="https://docs.phalcon.io/%d.%d/" target="_new">%s</a></div>',
            $phalconKit->get(),
            $phalcon->getPart(PhalconVersion::VERSION_MAJOR),
            $phalcon->getPart(PhalconVersion::VERSION_MEDIUM),
            $phalcon->get()
        );
    }
    
    /**
     * Intercept the rendered HTML and rewrite class links.
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

        assert(is_string($html));
        $html = preg_replace(
            '#<thead>\s*<tr>\s*<th>Key</th>\s*</tr>\s*<tr>\s*<th>Value</th>\s*</tr>\s*</thead>#',
            '<thead><tr><th class="key">Key</th><th>Value</th></tr></thead>',
            $html
        );

        assert(is_string($html));
        $html = preg_replace(
            '~<thead>\s*<tr>\s*<th>\#</th>\s*</tr>\s*<tr>\s*<th>Path</th>\s*</tr>\s*</thead>~',
            '<thead><tr><th class="number">#</th><th>Path</th></tr></thead>',
            $html
        );

        assert(is_string($html));
        $html = preg_replace(
            '#<thead>\s*<tr>\s*<th>Memory</th>\s*</tr>\s*<tr>\s*<th></th>\s*</tr>\s*</thead>#',
            '<thead><tr><th class="key">Memory</th><th>Value</th></tr></thead>',
            $html
        );
        
        // --- Add Phalcon Kit class links ---
        assert(is_string($html));
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
        
        assert(is_string($html));
        return $html;
    }
    
    
    #[\Override]
    public function getCssSources(): string
    {
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
    
    #[\Override]
    public function getJsSources(): string
    {
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
