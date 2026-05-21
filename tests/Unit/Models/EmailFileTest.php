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

use PhalconKit\Models\Abstracts\EmailFileAbstract;
use PhalconKit\Models\Abstracts\Interfaces\EmailFileAbstractInterface;
use PhalconKit\Models\EmailFile;
use PhalconKit\Models\Interfaces\EmailFileInterface;

/**
 * Class EmailFileTest
 *
 * This class contains unit tests for the User class.
 */
class EmailFileTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public EmailFileInterface $emailFile;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->emailFile = new EmailFile();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(EmailFile::class, $this->emailFile);
        $this->assertInstanceOf(EmailFileInterface::class, $this->emailFile);
    
        // Abstract
        $this->assertInstanceOf(EmailFileAbstract::class, $this->emailFile);
        $this->assertInstanceOf(EmailFileAbstractInterface::class, $this->emailFile);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->emailFile);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->emailFile);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->emailFile);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->emailFile);
    }

    public function testInitialize(): void
    {
        $this->emailFile->initialize();

        $this->assertSame('email_file', $this->emailFile->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->emailFile->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->emailFile->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->emailFile->setId($value);
        $this->assertEquals($value, $this->emailFile->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->emailFile->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->emailFile->setUuid($value);
        $this->assertEquals($value, $this->emailFile->getUuid());
    }

    public function testGetEmailId(): void
    {
        $this->assertEquals(null, $this->emailFile->getEmailId());
    }
    
    public function testSetEmailId(): void
    {
        $value = uniqid();
        $this->emailFile->setEmailId($value);
        $this->assertEquals($value, $this->emailFile->getEmailId());
    }

    public function testGetFileId(): void
    {
        $this->assertEquals(null, $this->emailFile->getFileId());
    }
    
    public function testSetFileId(): void
    {
        $value = uniqid();
        $this->emailFile->setFileId($value);
        $this->assertEquals($value, $this->emailFile->getFileId());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->emailFile->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->emailFile->setDeleted($value);
        $this->assertEquals($value, $this->emailFile->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->emailFile->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->emailFile->setCreatedAt($value);
        $this->assertEquals($value, $this->emailFile->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->emailFile->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->emailFile->setCreatedBy($value);
        $this->assertEquals($value, $this->emailFile->getCreatedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->emailFile->getColumnMap());
    }
}
