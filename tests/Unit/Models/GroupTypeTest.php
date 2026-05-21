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

use PhalconKit\Models\Abstracts\GroupTypeAbstract;
use PhalconKit\Models\Abstracts\Interfaces\GroupTypeAbstractInterface;
use PhalconKit\Models\GroupType;
use PhalconKit\Models\Interfaces\GroupTypeInterface;

/**
 * Class GroupTypeTest
 *
 * This class contains unit tests for the User class.
 */
class GroupTypeTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public GroupTypeInterface $groupType;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->groupType = new GroupType();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(GroupType::class, $this->groupType);
        $this->assertInstanceOf(GroupTypeInterface::class, $this->groupType);
    
        // Abstract
        $this->assertInstanceOf(GroupTypeAbstract::class, $this->groupType);
        $this->assertInstanceOf(GroupTypeAbstractInterface::class, $this->groupType);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->groupType);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->groupType);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->groupType);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->groupType);
    }

    public function testInitialize(): void
    {
        $this->groupType->initialize();

        $this->assertSame('group_type', $this->groupType->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->groupType->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->groupType->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->groupType->setId($value);
        $this->assertEquals($value, $this->groupType->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->groupType->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->groupType->setUuid($value);
        $this->assertEquals($value, $this->groupType->getUuid());
    }

    public function testGetGroupId(): void
    {
        $this->assertEquals(null, $this->groupType->getGroupId());
    }
    
    public function testSetGroupId(): void
    {
        $value = uniqid();
        $this->groupType->setGroupId($value);
        $this->assertEquals($value, $this->groupType->getGroupId());
    }

    public function testGetTypeId(): void
    {
        $this->assertEquals(null, $this->groupType->getTypeId());
    }
    
    public function testSetTypeId(): void
    {
        $value = uniqid();
        $this->groupType->setTypeId($value);
        $this->assertEquals($value, $this->groupType->getTypeId());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->groupType->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->groupType->setPosition($value);
        $this->assertEquals($value, $this->groupType->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->groupType->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->groupType->setDeleted($value);
        $this->assertEquals($value, $this->groupType->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->groupType->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->groupType->setCreatedAt($value);
        $this->assertEquals($value, $this->groupType->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->groupType->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->groupType->setCreatedBy($value);
        $this->assertEquals($value, $this->groupType->getCreatedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->groupType->getColumnMap());
    }
}
