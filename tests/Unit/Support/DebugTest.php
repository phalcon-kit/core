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
        $this->assertStringEndsWith('</style>', $css);
    }

    public function testGetJsSourcesReturnsInlineScriptBlock(): void
    {
        $js = (new Debug())->getJsSources();

        $this->assertStringStartsWith('<script>', $js);
        $this->assertStringContainsString('DOMContentLoaded', $js);
        $this->assertStringContainsString('Show full file', $js);
        $this->assertStringEndsWith('</script>', $js);
    }

    public function testRenderHtmlLinksPhalconKitClasses(): void
    {
        $html = (new Debug())->renderHtml(new \RuntimeException(Debug::class));

        $this->assertStringContainsString('RuntimeException', $html);
        $this->assertStringContainsString(
            'https://github.com/phalcon-kit/docs/tree/main/docs/api/classes/PhalconKit/Support/Debug/',
            $html
        );
        $this->assertStringContainsString('PhalconKit\\Support\\Debug', $html);
    }
}
