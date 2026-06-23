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
        $this->assertStringContainsString('Phalcon Kit', $result);
        $this->assertStringContainsString('Phalcon Framework', $result);
    }

    public function testGetVersionLinksToCurrentDocumentationMajorMinor(): void
    {
        $phalconVersion = new PhalconVersion();
        $expectedDocsUrl = sprintf(
            'https://docs.phalcon.io/%d.%d/',
            $phalconVersion->getPart(PhalconVersion::VERSION_MAJOR),
            $phalconVersion->getPart(PhalconVersion::VERSION_MEDIUM)
        );

        $this->assertStringContainsString($expectedDocsUrl, (new Debug())->getVersion());
    }

    public function testGetCssSourcesReturnsInlineStyleBlock(): void
    {
        $css = (new Debug())->getCssSources();

        $this->assertStringStartsWith('<style>', $css);
        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('.error-main', $css);
        $this->assertStringContainsString('#tabs{width:100%;min-width:0;overflow:hidden;background:var(--bg-panel)}', $css);
        $this->assertStringContainsString('--panel-pad-x:24px;--panel-pad-y:20px;--code-pad-x:16px;', $css);
        $this->assertStringContainsString('#tabs>ul{display:none;', $css);
        $this->assertStringContainsString('#tabs.debug-js>ul{display:flex}', $css);
        $this->assertStringContainsString('#tabs>div[id^=error-tabs-],#tabs>div[id]{display:block;', $css);
        $this->assertStringContainsString('#tabs.debug-js>div[id^=error-tabs-],#tabs.debug-js>div[id]{display:none;', $css);
        $this->assertStringContainsString('.superglobal-detail th.key,.superglobal-detail td.key,#memory th:first-child,#memory td:first-child{width:220px;', $css);
        $this->assertStringContainsString('#files th.number,#files td:first-child{width:64px;', $css);
        $this->assertStringContainsString('pre.prettyprint{display:block;inline-size:100%;max-inline-size:100%;', $css);
        $this->assertStringContainsString('#tabs:not(.debug-js) pre.prettyprint{display:none}', $css);
        $this->assertStringContainsString('contain:inline-size;', $css);
        $this->assertStringContainsString('pre.prettyprint .code-line{display:inline-flex;min-width:100%;width:auto;', $css);
        $this->assertStringEndsWith('</style>', $css);
    }

    public function testGetJsSourcesReturnsInlineScriptBlock(): void
    {
        $js = (new Debug())->getJsSources();

        $this->assertStringStartsWith('<script>', $js);
        $this->assertStringContainsString('DOMContentLoaded', $js);
        $this->assertStringContainsString('classList.add("debug-js")', $js);
        $this->assertStringContainsString('a[href^="#"]', $js);
        $this->assertStringContainsString('Show full file', $js);
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
        $this->assertStringContainsString('<thead><tr><th class="key">Key</th><th>Value</th></tr></thead>', $html);
        $this->assertStringContainsString('<thead><tr><th class="number">#</th><th>Path</th></tr></thead>', $html);
        $this->assertStringContainsString('Memory usage (real)', $html);
        $this->assertStringContainsString('Peak usage', $html);
        $this->assertStringContainsString('PhalconKit\\Support\\Debug', $html);
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
