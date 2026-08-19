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

use PhalconKit\Support\Php;
use PhalconKit\Tests\Unit\AbstractUnit;

class PhpTest extends AbstractUnit
{
    /**
     * @var Php Object used for testing.
     */
    protected Php $php;
    
    protected function setUp(): void
    {
        $this->php = new Php();
    }
    
    /**
     * Test all possible SAPI values
     */
    public function testIsCli(): void
    {
        $sapis = ['cli', 'cgi', 'apache', 'phpdbg', '', 'other', 'apache', 'apache2handler', 'cgi-fcgi', 'cli-server', 'embed', 'fpm-fcgi', 'litespeed'];
        foreach($sapis as $sapi) {
            switch ($sapi) {
                case 'cli':
                case 'phpdbg':
                    $this->assertTrue(Php::isCli($sapi), "Method isCli did not return true for $sapi SAPI when expected.");
                    break;
                default:
                    $this->assertFalse(Php::isCli($sapi), "Method isCli did not return false for $sapi SAPI when expected.");
            }
        }
    }
    
    /**
     * Test to make sure trustForwardedProto method correctly sets $_SERVER['HTTPS'] to 'on'
     * if $_SERVER['HTTP_X_FORWARDED_PROTO'] starts with 'https'.
     */
    public function testTrustForwardedProto(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        Php::trustForwardedProto();
        $this->assertEquals('on', $_SERVER['HTTPS']);
        
        // Resetting for other tests
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
    }

    public function testTrustForwardedProtoAcceptsForwardedProtoListStartingWithHttps(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https,http';
        Php::trustForwardedProto();

        $this->assertSame('on', $_SERVER['HTTPS']);

        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
    }
    
    /**
     * Test to make sure trustForwardedProto method does not change $_SERVER['HTTPS']
     * if $_SERVER['HTTP_X_FORWARDED_PROTO'] does not start with 'https'.
     */
    public function testTrustForwardedProtoDoesNotChangeHTTPS(): void
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        Php::trustForwardedProto();
        $this->assertEquals('off', $_SERVER['HTTPS']);
        
        // Resetting for other tests
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
    }
    
    /**
     * Test to make sure trustForwardedProto method does not set $_SERVER['HTTPS']
     * if $_SERVER['HTTP_X_FORWARDED_PROTO'] is not set.
     */
    public function testTrustForwardedProtoDoesNotSetHTTPS(): void
    {
        Php::trustForwardedProto();
        $this->assertArrayNotHasKey('HTTPS', $_SERVER);
        
        // Resetting for other tests
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
    }

    public function testTrustForwardedProtoIgnoresEmptyForwardedProto(): void
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = '';
        Php::trustForwardedProto();

        $this->assertSame('off', $_SERVER['HTTPS']);

        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
    }
    
    /**
     * @covers \PhalconKit\Support\Php::debug
     */
    public function testDebugEnablesErrorReportingWhenDebugFlagIsTrue(): void
    {
        // Arrange
        $originalErrorReporting = error_reporting();
        $originalDisplayStartupErrors = ini_get('display_startup_errors');
        $originalDisplayErrors = ini_get('display_errors');
        
        // Act
        Php::debug(true);
        
        // Assert
        $this->assertSame(E_ALL, error_reporting());
        $this->assertSame('1', ini_get('display_startup_errors'));
        $this->assertSame('1', ini_get('display_errors'));
        
        // Clean up
        error_reporting($originalErrorReporting);
        ini_set('display_startup_errors', $originalDisplayStartupErrors);
        ini_set('display_errors', $originalDisplayErrors);
    }
    
    /**
     * @covers \PhalconKit\Support\Php::debug
     */
    public function testDebugDisablesErrorReportingWhenDebugFlagIsFalse(): void
    {
        // Arrange
        $originalErrorReporting = error_reporting();
        $originalDisplayStartupErrors = ini_get('display_startup_errors');
        $originalDisplayErrors = ini_get('display_errors');
        
        // Act
        Php::debug(false);
        
        // Assert
        $this->assertSame(-1, error_reporting());
        $this->assertSame('0', ini_get('display_startup_errors'));
        $this->assertSame('0', ini_get('display_errors'));
        
        // Clean up
        error_reporting($originalErrorReporting);
        ini_set('display_startup_errors', $originalDisplayStartupErrors);
        ini_set('display_errors', $originalDisplayErrors);
    }
    
    /**
     * Test the set method in the Php class
     * @covers \PhalconKit\Support\Php::set
     */
    public function testSet()
    {
        // Prepare the configuration options for the application.
        $config = [
            'timezone' => 'Asia/Kolkata',
            'encoding' => 'UTF-8',
            'locale' => 'en_CA',
            'memoryLimit' => '1G',
            'timeoutLimit' => '30'
        ];
        
        // Call the set method with the prepared configuration.
        Php::set($config);
        
        // Assert that the appropriate options were set correctly.
        $this->assertEquals('Asia/Kolkata', date_default_timezone_get());
        $this->assertEquals('UTF-8', mb_internal_encoding());
        $this->assertEquals('UTF-8', mb_http_output());
        $this->assertEquals('1G', ini_get('memory_limit'));
        $this->assertEquals('30', ini_get('max_execution_time'));
    }
}
