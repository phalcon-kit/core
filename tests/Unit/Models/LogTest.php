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

namespace PhalconKit\Tests\Unit\Models;

use PhalconKit\Models\Abstracts\LogAbstract;
use PhalconKit\Models\Abstracts\Interfaces\LogAbstractInterface;
use PhalconKit\Models\Log;
use PhalconKit\Models\Interfaces\LogInterface;

/**
 * Class LogTest
 *
 * This class contains unit tests for the User class.
 */
class LogTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public LogInterface $log;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->log = new Log();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Log::class, $this->log);
        $this->assertInstanceOf(LogInterface::class, $this->log);
    
        // Abstract
        $this->assertInstanceOf(LogAbstract::class, $this->log);
        $this->assertInstanceOf(LogAbstractInterface::class, $this->log);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->log);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->log);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->log);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->log);
    }

    public function testInitialize(): void
    {
        $this->log->initialize();

        $this->assertSame('log', $this->log->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->log->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->log->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->log->setId($value);
        $this->assertEquals($value, $this->log->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->log->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->log->setUuid($value);
        $this->assertEquals($value, $this->log->getUuid());
    }

    public function testGetLevel(): void
    {
        $this->assertEquals(null, $this->log->getLevel());
    }
    
    public function testSetLevel(): void
    {
        $value = uniqid();
        $this->log->setLevel($value);
        $this->assertEquals($value, $this->log->getLevel());
    }

    public function testGetType(): void
    {
        $this->assertEquals('\'other\'', $this->log->getType());
    }
    
    public function testSetType(): void
    {
        $value = uniqid();
        $this->log->setType($value);
        $this->assertEquals($value, $this->log->getType());
    }

    public function testGetMessage(): void
    {
        $this->assertEquals(null, $this->log->getMessage());
    }
    
    public function testSetMessage(): void
    {
        $value = uniqid();
        $this->log->setMessage($value);
        $this->assertEquals($value, $this->log->getMessage());
    }

    public function testGetContext(): void
    {
        $this->assertEquals(null, $this->log->getContext());
    }
    
    public function testSetContext(): void
    {
        $value = uniqid();
        $this->log->setContext($value);
        $this->assertEquals($value, $this->log->getContext());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->log->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->log->setCreatedAt($value);
        $this->assertEquals($value, $this->log->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->log->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->log->setCreatedBy($value);
        $this->assertEquals($value, $this->log->getCreatedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->log->getColumnMap());
    }
}
