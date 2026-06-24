<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Support;

use PhalconKit\Support\Debug;
use PhalconKit\Support\Debug\Renderer\HtmlRenderer;
use PhalconKit\Support\Version as PhalconKitVersion;
use Phalcon\Support\Version as PhalconVersion;
use PhalconKit\Tests\Unit\AbstractUnit;

/**
 * Class VersionTest
 * @package Tests\Unit
 */
class DebugTest extends AbstractUnit
{
    public Debug $debug;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->debug = $this->di->get('debug');
    }
    
    public function testDebugFromDi(): void
    {
        $this->assertInstanceOf(Debug::class, $this->debug);
        $this->testGetVersion($this->debug);
    }
    
    public function testGetVersion(?Debug $debug = null): void
    {
        $debug ??= new Debug();
        
        $result = $debug->getVersion();
        
        $phalconKitVersion = new PhalconKitVersion();
        $phalconVersion = new PhalconVersion();
        
        $this->assertStringContainsString($phalconKitVersion->get(), $result);
        $this->assertStringContainsString($phalconVersion->get(), $result);
        $this->assertStringContainsString('PhalconKit', $result);
        $this->assertStringContainsString('Phalcon', $result);
    }

    public function testDebugInstallsPhalconKitHtmlRenderer(): void
    {
        $this->assertInstanceOf(HtmlRenderer::class, (new Debug())->getRenderer());
    }

    public function testGetCssSourcesReturnsInlineStyleBlock(): void
    {
        $css = (new Debug())->getCssSources();

        $this->assertStringStartsWith('<style>', $css);
        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('--table-head:#e7e7e7;', $css);
        $this->assertStringContainsString('.wrap{width:min(1160px,100%);', $css);
        $this->assertStringContainsString('.masthead{display:flex;', $css);
        $this->assertStringContainsString('.brand-logo{display:block;', $css);
        $this->assertStringContainsString('.meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;', $css);
        $this->assertStringContainsString('.tabs{display:flex;', $css);
        $this->assertStringContainsString('.tab.is-active{background:var(--text);color:var(--invert)}', $css);
        $this->assertStringContainsString('.panel.is-active{display:block}', $css);
        $this->assertStringContainsString('.frame-head{display:grid;', $css);
        $this->assertStringContainsString('button.frame-head{cursor:pointer}', $css);
        $this->assertStringContainsString('.frame-code-body[hidden]{display:none}', $css);
        $this->assertStringContainsString('--code-highlight-bg:#fff;--code-highlight-text:#000;', $css);
        $this->assertStringContainsString('scrollbar-color:var(--line) var(--bg)', $css);
        $this->assertStringContainsString('.tabs::-webkit-scrollbar{width:8px;height:8px}', $css);
        $this->assertStringContainsString('font-size:0;line-height:0;overflow:hidden', $css);
        $this->assertStringContainsString('.chev::before{content:"";display:block;', $css);
        $this->assertStringContainsString('clip-path:polygon(30% 15%,30% 85%,76% 50%)', $css);
        $this->assertStringContainsString('.frame.is-code-open .chev::before{transform:rotate(90deg)}', $css);
        $this->assertStringContainsString('.code-actions{position:absolute;', $css);
        $this->assertStringNotContainsString('margin-bottom:42px', $css);
        $this->assertStringContainsString('scrollbar-color:var(--code-highlight-bg) var(--code)', $css);
        $this->assertStringContainsString('.code::-webkit-scrollbar,pre.prettyprint::-webkit-scrollbar', $css);
        $this->assertStringContainsString(
            '.code::selection,.code *::selection,pre.prettyprint::selection,pre.prettyprint *::selection'
                . '{background:#fff;color:#000;text-shadow:none}',
            $css
        );
        $this->assertStringContainsString('.code tr.hl,.code tr.highlight{background:#fff!important;color:#000!important}', $css);
        $this->assertStringContainsString(
            '.code tr.hl td,.code tr.hl .ln,.code tr.hl .src,.code tr.highlight td,'
                . '.code tr.highlight .ln,.code tr.highlight .src'
                . '{background:#fff!important;color:#000!important;text-shadow:none!important}',
            $css
        );
        $this->assertStringContainsString(
            '.code tr.hl *,.code tr.highlight *,.code .highlight'
                . '{background:transparent!important;color:#000!important;text-shadow:none!important}',
            $css
        );
        $this->assertStringContainsString(
            '.code-line.hl,pre.prettyprint .highlight,pre.prettyprint mark'
                . '{background:#fff!important;color:#000!important;text-shadow:none!important}',
            $css
        );
        $this->assertStringContainsString('@keyframes frame-focus', $css);
        $this->assertStringContainsString('.grid,.superglobal-detail{width:100%;max-width:100%;', $css);
        $this->assertStringContainsString('table-layout:auto', $css);
        $this->assertStringContainsString('.kv-grid col.key-col{width:1%}', $css);
        $this->assertStringContainsString('.kv-grid col.value-col{width:auto}', $css);
        $this->assertStringContainsString('.files-grid col.number-col{width:70px}', $css);
        $this->assertStringContainsString('.grid thead th,.superglobal-detail thead th', $css);
        $this->assertStringContainsString('background:var(--table-head);color:var(--text);font-size:12px;font-weight:800', $css);
        $this->assertStringContainsString('.kv-grid thead th.key{min-width:10ch;white-space:nowrap;overflow-wrap:normal;word-break:normal}', $css);
        $this->assertStringContainsString('.grid tbody td,.superglobal-detail tbody td', $css);
        $this->assertStringContainsString('overflow-wrap:anywhere;word-break:break-word', $css);
        $this->assertStringContainsString('.grid tbody td.k,.superglobal-detail tbody th.key,.superglobal-detail tbody td.key', $css);
        $this->assertStringContainsString('background:var(--soft);white-space:nowrap', $css);
        $this->assertStringNotContainsString('.grid td.k,.grid th.key', $css);
        $this->assertStringContainsString('#files .grid th.number,#files .grid td.k', $css);
        $this->assertStringContainsString('.stats{display:grid;', $css);
        $this->assertStringNotContainsString('debug.css', $css);
        $this->assertStringNotContainsString('assets.phalcon.io', $css);
        $this->assertStringEndsWith('</style>', $css);
    }

    public function testGetJsSourcesReturnsInlineScriptBlock(): void
    {
        $js = (new Debug())->getJsSources();

        $this->assertStringStartsWith('<script>', $js);
        $this->assertStringContainsString('DOMContentLoaded', $js);
        $this->assertStringContainsString('.tab[data-tab]', $js);
        $this->assertStringContainsString('data-action="copy-trace"', $js);
        $this->assertStringContainsString('data-action="toggle-theme"', $js);
        $this->assertStringContainsString('#backtrace .frame.has-source', $js);
        $this->assertStringContainsString('data-action="toggle-frame-code"', $js);
        $this->assertStringContainsString('.frame-code-body', $js);
        $this->assertStringContainsString('data-action="focus-line"', $js);
        $this->assertStringContainsString('data-action="toggle-full-file"', $js);
        $this->assertStringContainsString('Show context', $js);
        $this->assertStringContainsString('Show full file', $js);
        $this->assertStringNotContainsString('debug.js', $js);
        $this->assertStringNotContainsString('assets.phalcon.io', $js);
        $this->assertStringEndsWith('</script>', $js);
    }

    public function testRenderHtmlLinksPhalconKitClasses(): void
    {
        $html = (new Debug())->renderHtml(new \RuntimeException(Debug::class));

        $this->assertStringContainsString('RuntimeException', $html);
        $this->assertStringContainsString(
            'https://github.com/phalcon-kit/docs/tree/main/docs/api/classes/PhalconKit/Support/Debug.md',
            $html
        );
        $this->assertStringContainsString("class='grid kv-grid'", $html);
        $this->assertStringContainsString('<colgroup><col class="key-col"><col class="value-col"></colgroup>', $html);
        $this->assertStringContainsString(
            '<thead><tr><th class="key" scope="col">Key</th><th scope="col">Value</th></tr></thead>',
            $html
        );
        $this->assertStringContainsString("class='grid files-grid'", $html);
        $this->assertStringContainsString('<colgroup><col class="number-col"><col></colgroup>', $html);
        $this->assertStringContainsString(
            '<thead><tr><th class="number" scope="col">#</th><th scope="col">Path</th></tr></thead>',
            $html
        );
        $this->assertStringContainsString('Memory usage (real)', $html);
        $this->assertStringContainsString('Peak usage', $html);
        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('PhalconKit Debug', $html);
        $this->assertStringContainsString('brand-logo', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString("<article class='frame", $html);
        $this->assertStringContainsString("data-action='toggle-frame-code'", $html);
        $this->assertStringContainsString("class='frame-code-body'", $html);
        $this->assertStringContainsString("class='code-actions'", $html);
        $this->assertStringContainsString("data-action='focus-line'", $html);
        $this->assertStringContainsString('Focus line', $html);
        $this->assertStringContainsString('Expand code', $html);
        $this->assertStringContainsString('Collapse code', $html);
        $this->assertStringNotContainsString("<details class='frame ", $html);
        $this->assertStringNotContainsString("class='frame-code-summary'", $html);
        $this->assertStringNotContainsString('Source context', $html);
        $this->assertStringNotContainsString('debug.css', $html);
        $this->assertStringNotContainsString('debug.js', $html);
        $this->assertStringNotContainsString('assets.phalcon.io', $html);
        $this->assertStringNotContainsString('logo--tablet.svg', $html);
        $this->assertStringNotContainsString('<img class=\'logo\'', $html);
        $this->assertStringContainsString('PhalconKit\\Support\\Debug', $html);
    }

    public function testBacktraceFrameNormalizationAvoidsDocumentWidePcreBacktracking(): void
    {
        $previousLimit = ini_get('pcre.backtrack_limit');
        $method = new \ReflectionMethod(Debug::class, 'normalizeBacktraceFrames');
        $html = "<section id='backtrace'>"
            . "<details class='frame app' open>"
            . "<summary><div class='frame-head'><span class='frame-num'>#0</span>"
            . "<span class='frame-call'><span class='fn'>demo</span></span>"
            . "<span class='chev' aria-hidden='true'>x</span></div></summary>"
            . str_repeat('x', 20_000)
            . '</details>'
            . '</section>';

        try {
            ini_set('pcre.backtrack_limit', '1000');

            $normalized = $method->invoke(null, $html);
        }
        finally {
            if (is_string($previousLimit)) {
                ini_set('pcre.backtrack_limit', $previousLimit);
            }
        }

        $this->assertIsString($normalized);
        $this->assertStringContainsString("<article class='frame app no-source'>", $normalized);
        $this->assertStringNotContainsString("<details class='frame", $normalized);
        $this->assertStringContainsString("class='frame-extra'", $normalized);
    }

    public function testUncaughtExceptionDebugPagesSetServerErrorStatus(): void
    {
        $debug = new class extends Debug {
            public function applyUncaughtExceptionStatusCode(): void
            {
                $this->setUncaughtExceptionStatusCode();
            }
        };

        $previousCode = http_response_code();

        try {
            http_response_code(200);

            $debug->applyUncaughtExceptionStatusCode();

            $this->assertSame(500, http_response_code());
        }
        finally {
            if (is_int($previousCode)) {
                http_response_code($previousCode);
            }
        }
    }
}
