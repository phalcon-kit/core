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
<style>.error-info,.error-main{box-shadow:0 20px 60px rgba(0,0,0,.15);background:var(--bg-panel)}#tabs,.error-info,.error-main{background:var(--bg-panel)}#tabs>ul,body,html{padding:0;margin:0;display:flex}#tabs,#tabs>ul,h1{border-bottom:1px solid var(--border)}#tabs>ul>li>a,a,body,h1,html{color:var(--text-main)}.superglobal-detail td,.superglobal-detail th{padding:6px 8px;color:#000}#error-tabs-1 table td,.superglobal-detail td{vertical-align:top;color:#000;word-break:break-word}#tabs>div[id^=error-tabs-],pre.prettyprint{line-height:1.4;overflow-x:auto;box-sizing:border-box;min-width:0;max-width:100%}#error-tabs-1 td>pre.prettyprint,pre.prettyprint{margin:12px 0 0}#tabs>ul>li>a,.version a,a:hover{text-decoration:none}.version a:hover,a{text-decoration:underline}:root{--bg-main:#ffffff;--bg-panel:#ffffff;--bg-alt:#f5f5f5;--bg-code:#0a0a0a;--text-main:#000000;--text-dim:#555555;--text-invert:#ffffff;--border:#000000;--border-light:#d0d0d0;--mono:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;--sans:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}body,html{background:var(--bg-main);font-family:var(--sans);font-size:15px;line-height:1.4;flex-direction:column;align-items:center;scroll-behavior:smooth;overflow-anchor:none;width:100%;max-width:100%;overflow-x:hidden}::selection{background:#000;color:#fff}::-moz-selection{background:#000;color:#fff}.error-main .error-file,.error-number{color:var(--text-dim);font-family:var(--mono)}h1{margin:0 0 .5rem;font-size:1.2rem;font-weight:600;padding-bottom:6px}.error-info,.error-main{width:100%;max-width:980px;box-sizing:border-box}.error-main{margin:2rem auto 0;border:1px solid var(--border);border-bottom:none;padding:16px 20px}.error-main .error-file{font-size:.8rem;display:block;margin-top:.5rem;word-break:break-all;opacity:.8}.error-info{margin:0 auto 2rem;border:1px solid var(--border);overflow:hidden}#tabs{width:100%;min-width:0;overflow:hidden}.superglobal-detail td,.superglobal-detail th,.version{border:1px solid var(--border-light)}#tabs>ul{list-style:none;justify-content:center;flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch}#tabs>ul::-webkit-scrollbar{height:6px}#tabs>ul::-webkit-scrollbar-track{background:#000}#tabs>ul::-webkit-scrollbar-thumb{background:#fff}#tabs>div[id^=error-tabs-],#tabs>ul>li,.version{background:var(--bg-panel)}#tabs>ul>li{flex:1 1 auto;min-width:120px;text-align:center;border-right:1px solid var(--border);font-size:.8rem}#tabs>ul>li:first-child{border-left:0}#tabs>ul>li:last-child{border-right:0}#tabs>ul>li>a{display:block;padding:10px 0;user-select:none;white-space:nowrap}#tabs>ul>li>a:hover{background:var(--bg-alt)}#tabs>ul>li.active>a{background:var(--text-main);color:var(--text-invert)}#tabs>div[id^=error-tabs-]{display:none;padding:16px 20px;font-size:.85rem;overflow-y:visible;max-width:100%;min-width:0;word-wrap:break-word;overflow-wrap:break-word}#tabs>div.active{display:block}#error-tabs-1 table,.superglobal-detail{width:100%;border-collapse:collapse;font-size:.8rem;table-layout:fixed;word-break:break-word}.superglobal-detail th{text-align:left;background:#eee;font-weight:600;white-space:nowrap}.superglobal-detail td{background:#fff}.superglobal-detail .key{font-weight:600;width:200px}#error-tabs-1 table td{border-top:1px solid var(--border-light);padding:8px 0;text-align:left;min-width:0;max-width:0;overflow:hidden}#error-tabs-1 td{padding-bottom:0}.error-number{width:40px;font-size:.75rem;padding-right:8px}.error-class,.error-function{font-weight:600;color:var(--text-main)}.error-file,.version{color:var(--text-dim);font-family:var(--mono)}.error-file{font-size:.75rem;margin-top:4px;word-break:break-all}pre.prettyprint{display:block;inline-size:100%;max-inline-size:100%;width:100%;max-width:100%;min-width:0;contain:inline-size;background:var(--bg-code);color:var(--text-invert);border:1px solid var(--border);font-family:var(--mono);font-size:.75rem;padding:12px 16px;white-space:pre;overflow-x:auto;overflow-y:auto;max-height:220px;tab-size:4;position:relative}pre.prettyprint::-webkit-scrollbar{width:6px;height:6px}pre.prettyprint::-webkit-scrollbar-track{background:#000}pre.prettyprint::-webkit-scrollbar-thumb{background:#fff}pre.prettyprint .code-ellipsis{display:block;text-align:center;color:#888;background:#000;font-style:italic;letter-spacing:2px;user-select:none}pre.prettyprint .code-line{display:inline-flex;min-width:100%;width:auto;max-width:none;color:#fff;background:#000;scroll-margin-top:40px;flex-direction:row;align-items:flex-start;white-space:pre;box-sizing:border-box}body::-webkit-scrollbar{width:8px}body::-webkit-scrollbar-track{background:#fff}body::-webkit-scrollbar-thumb{background:#000}pre.prettyprint .ln{display:inline-block;width:3em;min-width:3em;text-align:right;padding-right:1em;color:#777;user-select:none;flex-shrink:0}.expand-toggle:hover,pre.prettyprint .code-line.hl{background:#4f4f4f}pre.prettyprint .code-line.hl .ln{color:#000}pre.prettyprint ::selection{background:#fff;color:#000}pre.prettyprint ::-moz-selection{background:#fff;color:#000}pre.prettyprint .highlight,pre.prettyprint mark{background:#fff;color:#000;padding:0 2px}body :not(pre) mark{background:#000;color:#fff;padding:0 2px}.version{position:fixed;bottom:12px;right:12px;font-size:.8rem;padding:3px 6px;line-height:1.3;z-index:100;opacity:.9;white-space:nowrap}.version a{color:var(--text-main)}*{border-radius:0!important}.expand-toggle{display:block;margin:4px 0 12px auto;padding:4px 8px;max-width:calc(100% - 8px);font-size:.75rem;font-family:var(--mono);background:#000;color:#fff;border:1px solid #fff;cursor:pointer;transition:background .15s;position:sticky;bottom:12px;right:12px;z-index:999;white-space:nowrap}</style>
STYLE;
    }
    
    #[\Override]
    public function getJsSources(): string
    {
        return <<<'SCRIPT'
<script>document.addEventListener("DOMContentLoaded",()=>{let e=document.getElementById("tabs");if(e){let t=e.querySelectorAll('ul > li > a[href^="#error-tabs-"]'),l=e.querySelectorAll('div[id^="error-tabs-"]');function n(e){for(let n of t){let r=n.parentElement;r.classList.toggle("active",n.getAttribute("href")===e)}for(let i of l)i.classList.toggle("active",`#${i.id}`===e)}for(let r of t)r.addEventListener("click",e=>{e.preventDefault(),n(r.getAttribute("href"))});t.length>0&&n(t[0].getAttribute("href"))}let i=document.querySelectorAll("pre.prettyprint");for(let o of i){let a=o.className||"",s=a.match(/highlight:(\d+):(\d+)/),c=s?Number.parseInt(s[2],10):null,p=o.textContent.replaceAll("\r\n","\n"),f=p.split("\n"),u=f.length,d=e=>e.replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll("	","    ");function h(e,t){let l=[];for(let n=e;n<=t;n++){let r=n===c,i=d(f[n-1]||" ");l.push(`<span class="code-line${r?" hl":""}">${i}</span>`)}return l}function g(){let e=o.querySelector(".code-line.hl");e&&requestAnimationFrame(()=>{o.scrollTo({top:e.offsetTop-o.clientHeight/2,behavior:"instant"})})}function A(e,t){let l=document.createElement("button");l.className="expand-toggle",l.textContent=e,l.addEventListener("click",e=>{e.preventDefault(),e.stopPropagation(),t()}),o.appendChild(l)}function m(){let e=c?Math.max(1,c-7):1,t=c?Math.min(u,c+5):u,l=h(e,t);e>1&&l.unshift('<span class="code-ellipsis">…</span>'),t<u&&l.push('<span class="code-ellipsis">…</span>'),o.innerHTML=l.join("\n"),A("Show full file",v),g()}function v(){o.innerHTML=h(1,u).join("\n"),A("Collapse",m),g()}m()}});</script>
SCRIPT;
    }
}
