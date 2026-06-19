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

use Phalcon\Support\Version as PhalconVersion;
use PhalconKit\Support\Version as PhalconKitVersion;
use PhalconKit\Tests\Unit\AbstractUnit;

/**
 * Class VersionTest
 * @package Tests\Unit
 */
class VersionTest extends AbstractUnit
{
    /**
     * Testing the version class
     */
    public function testVersion(): void
    {
        $version = new PhalconKitVersion();
        $phalconVersion = new PhalconVersion();
        
        // Config must match Phalcon Kit version
        $this->assertNotEmpty($this->bootstrap->config->path('core.version'));
        $this->assertEquals($this->bootstrap->config->path('core.version'), $version->get());
        
        // Config must match Phalcon Framework version
        $this->assertNotEmpty($this->bootstrap->config->path('phalcon.version'));
        $this->assertEquals($this->bootstrap->config->path('phalcon.version'), $phalconVersion->get());
        
        // Test version->get()
        $this->assertSame('3.5.1', $version->get());
        $this->assertNotEmpty($version->get());
        $this->assertIsString($version->get());
        
        // Test version->getId()
        $this->assertNotEmpty($version->getId());
        $this->assertIsString($version->getId());
        
        // Test version->getPart()
        $this->assertIsString($version->getPart(PhalconVersion::VERSION_MAJOR));
        $this->assertIsString($version->getPart(PhalconVersion::VERSION_MEDIUM));
        $this->assertIsString($version->getPart(PhalconVersion::VERSION_MINOR));
        $this->assertIsString($version->getPart(PhalconVersion::VERSION_SPECIAL));
        $this->assertIsString($version->getPart(PhalconVersion::VERSION_SPECIAL_NUMBER));
        
        // Match version->get() with version->getPart()
        $specialNumber = $version->getPart(PhalconVersion::VERSION_SPECIAL_NUMBER);
        $this->assertEquals(
            $version->get(),
            $version->getPart(PhalconVersion::VERSION_MAJOR) . '.' .
            $version->getPart(PhalconVersion::VERSION_MEDIUM) . '.' .
            $version->getPart(PhalconVersion::VERSION_MINOR) .
            $version->getPart(PhalconVersion::VERSION_SPECIAL) .
            ($specialNumber ?: '')
        );
        
        $this->assertInstanceOf(PhalconVersion::class, $version);
    }
}
