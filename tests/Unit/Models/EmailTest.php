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

use PhalconKit\Models\Abstracts\EmailAbstract;
use PhalconKit\Models\Abstracts\Interfaces\EmailAbstractInterface;
use PhalconKit\Models\Email;
use PhalconKit\Models\Interfaces\EmailInterface;

/**
 * Class EmailTest
 *
 * This class contains unit tests for the User class.
 */
class EmailTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public EmailInterface $email;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->email = new Email();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Email::class, $this->email);
        $this->assertInstanceOf(EmailInterface::class, $this->email);
    
        // Abstract
        $this->assertInstanceOf(EmailAbstract::class, $this->email);
        $this->assertInstanceOf(EmailAbstractInterface::class, $this->email);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->email);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->email);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->email);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->email);
    }

    public function testInitialize(): void
    {
        $this->email->initialize();

        $this->assertSame('email', $this->email->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->email->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->email->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->email->setId($value);
        $this->assertEquals($value, $this->email->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->email->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->email->setUuid($value);
        $this->assertEquals($value, $this->email->getUuid());
    }

    public function testGetTemplateId(): void
    {
        $this->assertEquals(null, $this->email->getTemplateId());
    }
    
    public function testSetTemplateId(): void
    {
        $value = uniqid();
        $this->email->setTemplateId($value);
        $this->assertEquals($value, $this->email->getTemplateId());
    }

    public function testGetFrom(): void
    {
        $this->assertEquals(null, $this->email->getFrom());
    }
    
    public function testSetFrom(): void
    {
        $value = uniqid();
        $this->email->setFrom($value);
        $this->assertEquals($value, $this->email->getFrom());
    }

    public function testGetReplyTo(): void
    {
        $this->assertEquals('NULL', $this->email->getReplyTo());
    }
    
    public function testSetReplyTo(): void
    {
        $value = uniqid();
        $this->email->setReplyTo($value);
        $this->assertEquals($value, $this->email->getReplyTo());
    }

    public function testGetReturnPath(): void
    {
        $this->assertEquals('NULL', $this->email->getReturnPath());
    }
    
    public function testSetReturnPath(): void
    {
        $value = uniqid();
        $this->email->setReturnPath($value);
        $this->assertEquals($value, $this->email->getReturnPath());
    }

    public function testGetReadReceiptTo(): void
    {
        $this->assertEquals('NULL', $this->email->getReadReceiptTo());
    }
    
    public function testSetReadReceiptTo(): void
    {
        $value = uniqid();
        $this->email->setReadReceiptTo($value);
        $this->assertEquals($value, $this->email->getReadReceiptTo());
    }

    public function testGetPriority(): void
    {
        $this->assertEquals(3, $this->email->getPriority());
    }
    
    public function testSetPriority(): void
    {
        $value = uniqid();
        $this->email->setPriority($value);
        $this->assertEquals($value, $this->email->getPriority());
    }

    public function testGetTo(): void
    {
        $this->assertEquals(null, $this->email->getTo());
    }
    
    public function testSetTo(): void
    {
        $value = uniqid();
        $this->email->setTo($value);
        $this->assertEquals($value, $this->email->getTo());
    }

    public function testGetCc(): void
    {
        $this->assertEquals('NULL', $this->email->getCc());
    }
    
    public function testSetCc(): void
    {
        $value = uniqid();
        $this->email->setCc($value);
        $this->assertEquals($value, $this->email->getCc());
    }

    public function testGetBcc(): void
    {
        $this->assertEquals('NULL', $this->email->getBcc());
    }
    
    public function testSetBcc(): void
    {
        $value = uniqid();
        $this->email->setBcc($value);
        $this->assertEquals($value, $this->email->getBcc());
    }

    public function testGetSubject(): void
    {
        $this->assertEquals(null, $this->email->getSubject());
    }
    
    public function testSetSubject(): void
    {
        $value = uniqid();
        $this->email->setSubject($value);
        $this->assertEquals($value, $this->email->getSubject());
    }

    public function testGetContent(): void
    {
        $this->assertEquals(null, $this->email->getContent());
    }
    
    public function testSetContent(): void
    {
        $value = uniqid();
        $this->email->setContent($value);
        $this->assertEquals($value, $this->email->getContent());
    }

    public function testGetMeta(): void
    {
        $this->assertEquals('NULL', $this->email->getMeta());
    }
    
    public function testSetMeta(): void
    {
        $value = uniqid();
        $this->email->setMeta($value);
        $this->assertEquals($value, $this->email->getMeta());
    }

    public function testGetSent(): void
    {
        $this->assertEquals(null, $this->email->getSent());
    }
    
    public function testSetSent(): void
    {
        $value = uniqid();
        $this->email->setSent($value);
        $this->assertEquals($value, $this->email->getSent());
    }

    public function testGetSentAt(): void
    {
        $this->assertEquals('NULL', $this->email->getSentAt());
    }
    
    public function testSetSentAt(): void
    {
        $value = uniqid();
        $this->email->setSentAt($value);
        $this->assertEquals($value, $this->email->getSentAt());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->email->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->email->setDeleted($value);
        $this->assertEquals($value, $this->email->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->email->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->email->setCreatedAt($value);
        $this->assertEquals($value, $this->email->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->email->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->email->setCreatedBy($value);
        $this->assertEquals($value, $this->email->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->email->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->email->setUpdatedAt($value);
        $this->assertEquals($value, $this->email->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->email->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->email->setUpdatedBy($value);
        $this->assertEquals($value, $this->email->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->email->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->email->setDeletedAt($value);
        $this->assertEquals($value, $this->email->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->email->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->email->setDeletedBy($value);
        $this->assertEquals($value, $this->email->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->email->getColumnMap());
    }
}
