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

namespace PhalconKit\Tests\Unit\Provider;

use PhalconKit\Tests\Unit\AbstractUnit;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

class ClamavTest extends AbstractUnit
{
    protected function setUp(): void
    {
        parent::setUp();
        try {
            $clamav = $this->getClamav();
        } catch (\Exception $e) {
            $this->markTestSkipped('Failed to initialize clamav socket interaction, skipping tests');
        }
    }
    
    public function getClamav(): Client
    {
        return $this->di->get('clamav');
    }
    
    public function testClamav(): void
    {
        $clamav = $this->getClamav();
        $this->assertInstanceOf(Client::class, $clamav);
    }
    
    public function testPing(): void
    {
        $clamav = $this->getClamav();
        $this->assertTrue($clamav->ping());
    }
    
    public function testVersion(): void
    {
        $clamav = $this->getClamav();
        $this->assertStringContainsString('ClamAV', $clamav->version());
    }
    
    public function testStats(): void
    {
        $clamav = $this->getClamav();
        $this->assertNotEmpty($clamav->stats());
    }
    
    public function testDisconnect(): void
    {
        $clamav = $this->getClamav();
        $this->assertTrue($clamav->disconnect());
    }
    
    public function testReload(): void
    {
        $clamav = $this->getClamav();
        $this->assertEquals('RELOADING', $clamav->reload());
    }
    
    public function testNegative(): void
    {
        $clamav = $this->getClamav();
        $clamav->startSession();
        
        $negativeFile = __FILE__;
        $result = $clamav->scanFile($negativeFile);
        
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isOk(), 'ok');
        $this->assertFalse($result->isError(), 'error');
        $this->assertFalse($result->isFound(), 'found');
        $this->assertIsString($result->getId(), 'isString id');
        $this->assertNotEmpty($result->getId(), 'notEmpty id');
        $this->assertEquals($negativeFile, $result->getFilename(), 'filename');
        $this->assertEmpty($result->getReason(), 'reason');
        
        $clamav->endSession();
    }
    
    // Positive EICAR coverage needs a fixture/download strategy that is safe
    // for local development, CI scanners, and package archives.
//    public function testPositive(): void
//    {
//        $clamav = $this->getClamav();
//        $clamav->startSession();
//        
//        $positiveFile = __DIR__ . '/../../Files/clamav-positive.txt';
//        $result = $clamav->scanFile($positiveFile);
//        
//        $this->assertInstanceOf(Result::class, $result);
//        $this->assertFalse($result->isOk(), 'ok');
//        $this->assertFalse($result->isError(), 'error');
//        $this->assertTrue($result->isFound(), 'found');
//        $this->assertIsString($result->getId(), 'isString id');
//        $this->assertNotEmpty($result->getId(), 'notEmpty id');
//        $this->assertEquals(realpath($positiveFile), $result->getFilename(), 'filename');
//        $this->assertEquals('Eicar-Signature', $result->getReason(), 'reason');
//        
//        $clamav->endSession();
//    }
    
    public function testNotFound(): void
    {
        $clamav = $this->getClamav();
        $clamav->startSession();
        
        $errorFile = uniqid('_', true);
        $result = $clamav->scanFile($errorFile);
        
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isOk(), 'ok');
        $this->assertFalse($result->isError(), 'error');
        $this->assertFalse($result->isFound(), 'found');
        $this->assertIsString($result->getId(), 'isString id');
        $this->assertNotEmpty($result->getId(), 'notEmpty id');
        $this->assertEquals($errorFile, $result->getFilename(), 'filename');
        $this->assertEquals('File path check', $result->getReason(), 'reason');
        
        $clamav->endSession();
    }
    
    public function testPositiveStream(): void
    {
        $clamav = $this->getClamav();
        $clamav->startSession();
        
        $result = $clamav->scanStream('X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*');
        
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isOk(), 'ok');
        $this->assertFalse($result->isError(), 'error');
        $this->assertTrue($result->isFound(), 'found');
        $this->assertIsString($result->getId(), 'isString id');
        $this->assertNotEmpty($result->getId(), 'notEmpty id');
        $this->assertEquals('stream', $result->getFilename(), 'filename');
        $this->assertEquals('Win.Test.EICAR_HDB-1', $result->getReason(), 'reason');
        
        $clamav->endSession();
    }
}
