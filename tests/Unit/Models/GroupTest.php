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

use PhalconKit\Models\Abstracts\GroupAbstract;
use PhalconKit\Models\Abstracts\Interfaces\GroupAbstractInterface;
use PhalconKit\Models\Group;
use PhalconKit\Models\Interfaces\GroupInterface;

/**
 * Class GroupTest
 *
 * This class contains unit tests for the User class.
 */
class GroupTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public GroupInterface $group;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->group = new Group();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Group::class, $this->group);
        $this->assertInstanceOf(GroupInterface::class, $this->group);
    
        // Abstract
        $this->assertInstanceOf(GroupAbstract::class, $this->group);
        $this->assertInstanceOf(GroupAbstractInterface::class, $this->group);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->group);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->group);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->group);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->group);
    }

    public function testInitialize(): void
    {
        $this->group->initialize();

        $this->assertSame('group', $this->group->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->group->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->group->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->group->setId($value);
        $this->assertEquals($value, $this->group->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->group->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->group->setUuid($value);
        $this->assertEquals($value, $this->group->getUuid());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->group->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->group->setKey($value);
        $this->assertEquals($value, $this->group->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->group->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->group->setLabel($value);
        $this->assertEquals($value, $this->group->getLabel());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->group->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->group->setPosition($value);
        $this->assertEquals($value, $this->group->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->group->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->group->setDeleted($value);
        $this->assertEquals($value, $this->group->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->group->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->group->setCreatedAt($value);
        $this->assertEquals($value, $this->group->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->group->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->group->setCreatedBy($value);
        $this->assertEquals($value, $this->group->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->group->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->group->setUpdatedAt($value);
        $this->assertEquals($value, $this->group->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->group->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->group->setUpdatedBy($value);
        $this->assertEquals($value, $this->group->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->group->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->group->setDeletedAt($value);
        $this->assertEquals($value, $this->group->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->group->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->group->setDeletedBy($value);
        $this->assertEquals($value, $this->group->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->group->getColumnMap());
    }
}
