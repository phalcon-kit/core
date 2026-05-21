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

use PhalconKit\Models\Abstracts\FlagAbstract;
use PhalconKit\Models\Abstracts\Interfaces\FlagAbstractInterface;
use PhalconKit\Models\Flag;
use PhalconKit\Models\Interfaces\FlagInterface;

/**
 * Class FlagTest
 *
 * This class contains unit tests for the User class.
 */
class FlagTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public FlagInterface $flag;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->flag = new Flag();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Flag::class, $this->flag);
        $this->assertInstanceOf(FlagInterface::class, $this->flag);
    
        // Abstract
        $this->assertInstanceOf(FlagAbstract::class, $this->flag);
        $this->assertInstanceOf(FlagAbstractInterface::class, $this->flag);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->flag);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->flag);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->flag);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->flag);
    }

    public function testInitialize(): void
    {
        $this->flag->initialize();

        $this->assertSame('flag', $this->flag->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->flag->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->flag->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->flag->setId($value);
        $this->assertEquals($value, $this->flag->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->flag->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->flag->setUuid($value);
        $this->assertEquals($value, $this->flag->getUuid());
    }

    public function testGetSiteId(): void
    {
        $this->assertEquals(null, $this->flag->getSiteId());
    }
    
    public function testSetSiteId(): void
    {
        $value = uniqid();
        $this->flag->setSiteId($value);
        $this->assertEquals($value, $this->flag->getSiteId());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->flag->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->flag->setKey($value);
        $this->assertEquals($value, $this->flag->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->flag->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->flag->setLabel($value);
        $this->assertEquals($value, $this->flag->getLabel());
    }

    public function testGetValue(): void
    {
        $this->assertEquals(1, $this->flag->getValue());
    }
    
    public function testSetValue(): void
    {
        $value = uniqid();
        $this->flag->setValue($value);
        $this->assertEquals($value, $this->flag->getValue());
    }

    public function testGetMeta(): void
    {
        $this->assertEquals('NULL', $this->flag->getMeta());
    }
    
    public function testSetMeta(): void
    {
        $value = uniqid();
        $this->flag->setMeta($value);
        $this->assertEquals($value, $this->flag->getMeta());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->flag->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->flag->setDeleted($value);
        $this->assertEquals($value, $this->flag->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->flag->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->flag->setCreatedAt($value);
        $this->assertEquals($value, $this->flag->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->flag->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->flag->setCreatedBy($value);
        $this->assertEquals($value, $this->flag->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->flag->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->flag->setUpdatedAt($value);
        $this->assertEquals($value, $this->flag->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->flag->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->flag->setUpdatedBy($value);
        $this->assertEquals($value, $this->flag->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->flag->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->flag->setDeletedAt($value);
        $this->assertEquals($value, $this->flag->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->flag->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->flag->setDeletedBy($value);
        $this->assertEquals($value, $this->flag->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->flag->getColumnMap());
    }
}
