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

namespace PhalconKit\Support\Debug\Renderer;

use PhalconKit\Support\Version as PhalconKitVersion;
use Phalcon\Support\Version as PhalconVersion;

/**
 * Renders Phalcon 5.16 debug reports with PhalconKit's inline theme.
 *
 * Phalcon 5.16 moved debug output behind a renderer/template contract. This
 * renderer uses that contract directly so debug pages do not load remote
 * assets and so PhalconKit's theme stays attached to the active render path.
 */
class HtmlRenderer extends \Phalcon\Support\Debug\Renderer\HtmlRenderer
{
    public function __construct()
    {
        $logo = $this->getLogoDataUri();

        $this->setTemplate('masthead', <<<HTML

    <div class='masthead'>
        <div class='brand'><img class='brand-logo' src='{$logo}' alt='' width='28' height='28' aria-hidden='true'><span>PhalconKit Debug</span></div>
        <div class='actions-top'>
            <button class='btn' data-action='copy-trace'>Copy trace</button>
            <button class='btn' data-action='toggle-theme' title='Toggle theme'>Theme</button>
            %version%
        </div>
    </div>
HTML);
    }

    private function getLogoDataUri(): string
    {
        return 'data:image/png;base64,' . str_replace(
            ["\r", "\n"],
            '',
            <<<'PNG'
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
PNG
        );
    }

    /**
     * Return inline CSS for PhalconKit's debug page theme.
     */
    #[\Override]
    public function getCssSources(string $uri): string
    {
        return <<<'STYLE'
<style>
:root{color-scheme:light;--bg:#fff;--panel:#fff;--soft:#f5f5f5;--table-head:#e7e7e7;--line:#000;--line-soft:#d7d7d7;--text:#000;--muted:#555;--invert:#fff;--code:#050505;--code-line:#141414;--code-highlight-bg:#fff;--code-highlight-text:#000;--mono:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;--sans:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
html[data-theme=dark]{color-scheme:dark;--bg:#050505;--panel:#111;--soft:#1d1d1d;--table-head:#2a2a2a;--line:#f1f1f1;--line-soft:#444;--text:#f7f7f7;--muted:#aaa;--invert:#000;--code:#000;--code-line:#151515}
*{box-sizing:border-box;border-radius:0!important}
html,body{min-height:100%;margin:0;background:var(--bg);color:var(--text);font:15px/1.45 var(--sans);overflow-x:hidden;scrollbar-color:var(--line) var(--bg);scrollbar-width:thin}
body{padding:0 16px 32px}
body::-webkit-scrollbar{width:8px;height:8px}
body::-webkit-scrollbar-track{background:var(--bg)}
body::-webkit-scrollbar-thumb{background:var(--line)}
body::-webkit-scrollbar-corner{background:var(--bg)}
::selection{background:var(--line);color:var(--invert)}
a{color:var(--text);text-decoration:underline;text-underline-offset:2px}
a:hover{text-decoration:none}
button{font:inherit}
.wrap{width:min(1160px,100%);margin:24px auto;border:1px solid var(--line);background:var(--panel);box-shadow:0 20px 70px rgba(0,0,0,.16)}
.masthead{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-bottom:1px solid var(--line);background:var(--panel)}
.brand,.actions-top{display:flex;align-items:center;gap:10px;min-width:0}
.brand{font-weight:700;letter-spacing:0}
.brand-logo{display:block;flex:0 0 28px;width:28px;height:28px;object-fit:contain}
.btn,.version-badge{display:inline-flex;align-items:center;min-height:30px;padding:5px 9px;border:1px solid var(--line);background:var(--panel);color:var(--text);text-decoration:none;cursor:pointer;font:12px/1.2 var(--mono);white-space:nowrap}
.btn:hover,.version-badge:hover,.tab:hover{background:var(--text);color:var(--invert);text-decoration:none}
.error-card{padding:18px 18px 16px;border-bottom:1px solid var(--line);background:var(--panel)}
.error-type{display:inline-block;margin:0 0 10px;padding:3px 6px;border:1px solid var(--line);font:12px/1.2 var(--mono)}
.error-message{margin:0;font-size:20px;line-height:1.25;font-weight:650;overflow-wrap:anywhere}
.meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;color:var(--muted);font:12px/1.35 var(--mono)}
.meta code{color:var(--text)}
.tabs{display:flex;overflow-x:auto;border-bottom:1px solid var(--line);background:var(--panel);scrollbar-color:var(--line) var(--panel);scrollbar-width:thin}
.tabs::-webkit-scrollbar{width:8px;height:8px}
.tabs::-webkit-scrollbar-track{background:var(--panel)}
.tabs::-webkit-scrollbar-thumb{background:var(--line)}
.tabs::-webkit-scrollbar-corner{background:var(--panel)}
.tab{flex:1 0 auto;min-width:132px;padding:10px 12px;border:0;border-right:1px solid var(--line);background:var(--panel);color:var(--text);cursor:pointer;text-align:center;font:12px/1.2 var(--mono);white-space:nowrap}
.tab:last-child{border-right:0}
.tab.is-active{background:var(--text);color:var(--invert)}
.count{opacity:.72}
.panel{display:none;padding:18px;border-bottom:1px solid var(--line-soft);background:var(--panel)}
.panel.is-active{display:block}
.bt-tools{display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px}
.frame{border:1px solid var(--line-soft);background:var(--panel);margin:0 0 12px}
.frame.has-source{border-color:var(--line)}
.frame-head{display:grid;grid-template-columns:auto minmax(0,1fr) auto auto;gap:10px;align-items:center;width:100%;padding:10px 12px;border:0;border-bottom:1px solid var(--line-soft);background:var(--soft);color:var(--text);text-align:left}
button.frame-head{cursor:pointer}
button.frame-head:hover{background:var(--panel)}
.frame-num{font:12px/1 var(--mono);color:var(--muted)}
.frame-call{min-width:0;overflow-wrap:anywhere;font-family:var(--mono);font-size:13px}
.frame-call .op{padding:0 3px;color:var(--muted)}
.frame-call .fn{font-weight:700}
.tag-app,.tag-vendor,.tag-internal{justify-self:end;padding:2px 5px;border:1px solid var(--line-soft);background:var(--panel);color:var(--muted);font:10px/1.2 var(--mono);text-transform:uppercase}
.frame-file{padding:9px 12px;border-bottom:1px solid var(--line-soft);color:var(--muted);font:12px/1.35 var(--mono);overflow-wrap:anywhere}
.frame-code-body{background:var(--panel)}
.frame-code-body[hidden]{display:none}
.chev{display:inline-grid;place-items:center;flex:0 0 18px;width:18px;height:18px;border:1px solid var(--line-soft);background:var(--panel);color:var(--text);font-size:0;line-height:0;overflow:hidden;transition:background .18s ease,color .18s ease,border-color .18s ease}
.chev::before{content:"";display:block;width:10px;height:10px;background:currentColor;clip-path:polygon(30% 15%,30% 85%,76% 50%);transform-origin:50% 50%;transition:transform .18s ease}
button.frame-head:hover .chev{border-color:var(--line);background:var(--text);color:var(--invert)}
.frame.is-code-open .chev::before{transform:rotate(90deg)}
.code-shell{position:relative;background:var(--code)}
.code-actions{position:absolute;right:10px;bottom:10px;z-index:2;display:flex;gap:8px;padding:4px;border:1px solid #fff;background:rgba(0,0,0,.86)}
.code-btn{min-height:26px;padding:4px 8px;font-size:11px}
.code{max-height:420px;overflow:auto;background:var(--code);scrollbar-color:var(--code-highlight-bg) var(--code);scrollbar-width:thin}
.code::-webkit-scrollbar,pre.prettyprint::-webkit-scrollbar{width:8px;height:8px}
.code::-webkit-scrollbar-track,pre.prettyprint::-webkit-scrollbar-track{background:var(--code)}
.code::-webkit-scrollbar-thumb,pre.prettyprint::-webkit-scrollbar-thumb{background:var(--code-highlight-bg);border:2px solid var(--code)}
.code::-webkit-scrollbar-corner,pre.prettyprint::-webkit-scrollbar-corner{background:var(--code)}
.code table{width:100%;border-collapse:collapse;font:12px/1.45 var(--mono);color:#fff}
.code td{padding:0 10px;vertical-align:top;border:0}
.code .ln{width:64px;padding:0 12px;color:#8a8a8a;text-align:right;user-select:none;background:#000}
.code .src{min-width:680px;white-space:pre;background:var(--code-line)}
.code::selection,.code *::selection,pre.prettyprint::selection,pre.prettyprint *::selection{background:#fff;color:#000;text-shadow:none}
.code tr:hover .src{background:#2d2d2d}
.code tr.hl,.code tr.highlight{background:#fff!important;color:#000!important}
.code tr.hl td,.code tr.hl .ln,.code tr.hl .src,.code tr.highlight td,.code tr.highlight .ln,.code tr.highlight .src{background:#fff!important;color:#000!important;text-shadow:none!important}
.code tr.hl *,.code tr.highlight *,.code .highlight{background:transparent!important;color:#000!important;text-shadow:none!important}
.code tr.is-focused .ln,.code tr.is-focused .src{animation:frame-focus .85s ease-out}
@keyframes frame-focus{0%{box-shadow:inset 0 0 0 2px #fff}100%{box-shadow:inset 0 0 0 0 transparent}}
.grid,.superglobal-detail{width:100%;max-width:100%;border-collapse:collapse;border:1px solid var(--line-soft);font:13px/1.45 var(--mono);table-layout:auto;background:var(--panel)}
.kv-grid col.key-col{width:1%}
.kv-grid col.value-col{width:auto}
.files-grid col.number-col{width:70px}
.grid thead th,.superglobal-detail thead th,.superglobal-detail th{padding:9px 10px;border:1px solid var(--line-soft);background:var(--table-head);color:var(--text);font-size:12px;font-weight:800;line-height:1.25;text-align:left;text-transform:uppercase;white-space:normal;overflow-wrap:anywhere}
.kv-grid thead th.key{min-width:10ch;white-space:nowrap;overflow-wrap:normal;word-break:normal}
.grid tbody td,.superglobal-detail tbody td,.superglobal-detail td{min-width:0;padding:8px 10px;border:1px solid var(--line-soft);vertical-align:top;white-space:normal;overflow-wrap:anywhere;word-break:break-word}
.grid tbody tr:nth-child(even) td,.superglobal-detail tbody tr:nth-child(even) td{background:rgba(127,127,127,.06)}
.grid tbody td.k,.superglobal-detail tbody th.key,.superglobal-detail tbody td.key{font-weight:700;color:var(--text);background:var(--soft);white-space:nowrap}
.grid td.v,.superglobal-detail td:not(.key){color:var(--text)}
#files .grid th.number,#files .grid td.k,#files th.number,#files td:first-child{width:70px;text-align:right;white-space:nowrap;font-family:var(--mono)}
#files .grid th:nth-child(2),#files .grid td.v,#files th:nth-child(2),#files td:nth-child(2){overflow-wrap:anywhere;word-break:break-word}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
.stat{border:1px solid var(--line);padding:12px;background:var(--soft)}
.stat .label{margin-bottom:8px;color:var(--muted);font:12px/1.2 var(--mono)}
.stat .value{font-size:24px;font-weight:700}
.stat small{font-size:12px;color:var(--muted)}
pre.prettyprint{display:block;width:100%;max-width:100%;max-height:280px;margin:12px 0;padding:14px 16px;overflow:auto;background:var(--code);color:#fff;border:1px solid var(--line);font:12px/1.5 var(--mono);white-space:pre;tab-size:4;scrollbar-color:var(--code-highlight-bg) var(--code);scrollbar-width:thin}
.code-line{display:inline-flex;min-width:100%;padding-right:16px}
.code-line.hl,pre.prettyprint .highlight,pre.prettyprint mark{background:#fff!important;color:#000!important;text-shadow:none!important}
.code-line.hl *,pre.prettyprint .highlight *,pre.prettyprint mark *{background:transparent!important;color:#000!important;text-shadow:none!important}
.expand-toggle:hover{background:#4b4b4b}
.code-ellipsis{display:block;text-align:center;color:#999;background:#000;letter-spacing:2px}
.expand-toggle{display:block;position:sticky;right:0;bottom:10px;margin:12px 0 0 auto;padding:5px 9px;border:1px solid #fff;background:#000;color:#fff;font:12px/1.2 var(--mono);cursor:pointer}
@media (max-width:760px){body{padding:0}.wrap{margin:0;border-left:0;border-right:0}.masthead{align-items:flex-start;flex-direction:column}.actions-top{width:100%;overflow-x:auto}.error-message{font-size:17px}.panel{padding:12px}.tab{min-width:116px}.kv-grid col.key-col{width:38%}.grid tbody td.k,.superglobal-detail tbody th.key,.superglobal-detail tbody td.key{white-space:normal}}
</style>
STYLE;
    }

    /**
     * Return inline JavaScript for tabs, theme toggling, and source previews.
     */
    #[\Override]
    public function getJsSources(string $uri): string
    {
        return <<<'SCRIPT'
<script>
document.addEventListener("DOMContentLoaded", () => {
    const root = document.documentElement;
    const storedTheme = window.localStorage ? localStorage.getItem("phalconkit-debug-theme") : null;

    if (storedTheme === "dark" || storedTheme === "light") {
        root.dataset.theme = storedTheme;
    }

    const tabs = document.querySelectorAll(".tab[data-tab]");
    const panels = document.querySelectorAll(".panel[id]");

    function activateTab(name) {
        for (const tab of tabs) {
            tab.classList.toggle("is-active", tab.dataset.tab === name);
        }

        for (const panel of panels) {
            panel.classList.toggle("is-active", panel.id === name);
        }
    }

    for (const tab of tabs) {
        tab.addEventListener("click", () => activateTab(tab.dataset.tab));
    }

    const active = document.querySelector(".tab.is-active[data-tab]") || tabs[0];
    if (active) {
        activateTab(active.dataset.tab);
    }

    const themeButton = document.querySelector('[data-action="toggle-theme"]');
    if (themeButton) {
        themeButton.addEventListener("click", () => {
            const next = root.dataset.theme === "dark" ? "light" : "dark";
            root.dataset.theme = next;

            if (window.localStorage) {
                localStorage.setItem("phalconkit-debug-theme", next);
            }
        });
    }

    const copyButton = document.querySelector('[data-action="copy-trace"]');
    if (copyButton && navigator.clipboard) {
        copyButton.addEventListener("click", async () => {
            const trace = document.getElementById("backtrace");

            if (!trace) {
                return;
            }

            await navigator.clipboard.writeText(trace.innerText.trim());
            copyButton.textContent = "Copied";
            window.setTimeout(() => {
                copyButton.textContent = "Copy trace";
            }, 1200);
        });
    }

    const expandAll = document.querySelector('[data-action="expand-all"]');
    const collapseAll = document.querySelector('[data-action="collapse-all"]');

    function setFrameCodeOpen(frame, open) {
        const body = frame.querySelector(":scope > .frame-code-body");
        const toggle = frame.querySelector('[data-action="toggle-frame-code"]');

        if (!body || !toggle) {
            return;
        }

        frame.classList.toggle("is-code-open", open);
        body.hidden = !open;
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
    }

    if (expandAll) {
        expandAll.addEventListener("click", () => {
            document.querySelectorAll("#backtrace .frame.has-source").forEach((frame) => setFrameCodeOpen(frame, true));
        });
    }

    if (collapseAll) {
        collapseAll.addEventListener("click", () => {
            document.querySelectorAll("#backtrace .frame.has-source").forEach((frame) => setFrameCodeOpen(frame, false));
        });
    }

    for (const frame of document.querySelectorAll("#backtrace .frame.has-source")) {
        const headerToggle = frame.querySelector('[data-action="toggle-frame-code"]');
        const code = frame.querySelector(":scope .code");
        const fullTemplate = frame.querySelector(":scope .code-full-template");
        const toggleFull = frame.querySelector('[data-action="toggle-full-file"]');
        const focusButton = frame.querySelector('[data-action="focus-line"]');
        const contextHtml = code ? code.innerHTML : "";

        if (headerToggle) {
            headerToggle.addEventListener("click", () => {
                setFrameCodeOpen(frame, !frame.classList.contains("is-code-open"));
            });
        }

        function focusLine() {
            if (!code) {
                return;
            }

            setFrameCodeOpen(frame, true);

            window.setTimeout(() => {
                const line = code.querySelector("tr.hl, .code-line.hl");

                if (!line) {
                    return;
                }

                line.scrollIntoView({ block: "center", inline: "nearest", behavior: "smooth" });
                line.classList.add("is-focused");
                window.setTimeout(() => line.classList.remove("is-focused"), 900);
            }, 0);
        }

        if (focusButton) {
            focusButton.addEventListener("click", (event) => {
                event.preventDefault();
                focusLine();
            });
        }

        if (code && fullTemplate && toggleFull) {
            toggleFull.addEventListener("click", (event) => {
                event.preventDefault();

                if (toggleFull.dataset.state === "full") {
                    code.innerHTML = contextHtml;
                    toggleFull.dataset.state = "context";
                    toggleFull.textContent = "Show full file";
                    focusLine();
                    return;
                }

                code.innerHTML = fullTemplate.innerHTML.trim();
                toggleFull.dataset.state = "full";
                toggleFull.textContent = "Show context";
                focusLine();
            });
        }
    }

    for (const pre of document.querySelectorAll("pre.prettyprint")) {
        const className = pre.className || "";
        const highlight = className.match(/highlight:(\d+):(\d+)/);
        const highlightLine = highlight ? Number.parseInt(highlight[2], 10) : null;
        const originalText = pre.textContent.replaceAll("\r\n", "\n");
        const lines = originalText.split("\n");

        if (lines.length <= 36) {
            continue;
        }

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

        function addToggle(label, callback) {
            const button = document.createElement("button");
            button.className = "expand-toggle";
            button.textContent = label;
            button.addEventListener("click", callback);
            pre.appendChild(button);
        }

        function renderPreview() {
            const first = highlightLine ? Math.max(1, highlightLine - 8) : 1;
            const last = highlightLine ? Math.min(lines.length, highlightLine + 8) : Math.min(28, lines.length);
            const html = renderLines(first, last);

            if (first > 1) {
                html.unshift('<span class="code-ellipsis">...</span>');
            }

            if (last < lines.length) {
                html.push('<span class="code-ellipsis">...</span>');
            }

            pre.innerHTML = html.join("\n");
            addToggle("Show full file", renderFull);
        }

        function renderFull() {
            pre.innerHTML = renderLines(1, lines.length).join("\n");
            addToggle("Collapse", renderPreview);
        }

        renderPreview();
    }
});
</script>
SCRIPT;
    }

    /**
     * Return a PhalconKit-aware version badge for the inline debug header.
     */
    #[\Override]
    public function getVersion(): string
    {
        $phalconKit = new PhalconKitVersion();
        $phalcon = new PhalconVersion();

        return sprintf(
            '<span class="version-badge">PhalconKit %s | Phalcon %s</span>',
            htmlspecialchars($phalconKit->get(), ENT_QUOTES),
            htmlspecialchars($phalcon->get(), ENT_QUOTES)
        );
    }
}
