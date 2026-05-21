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

use PhalconKit\Models\Abstracts\TypeAbstract;
use PhalconKit\Models\Abstracts\Interfaces\TypeAbstractInterface;
use PhalconKit\Models\Type;
use PhalconKit\Models\Interfaces\TypeInterface;

/**
 * Class TypeTest
 *
 * This class contains unit tests for the User class.
 */
class TypeTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public TypeInterface $type;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->type = new Type();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Type::class, $this->type);
        $this->assertInstanceOf(TypeInterface::class, $this->type);
    
        // Abstract
        $this->assertInstanceOf(TypeAbstract::class, $this->type);
        $this->assertInstanceOf(TypeAbstractInterface::class, $this->type);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->type);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->type);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->type);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->type);
    }

    public function testInitialize(): void
    {
        $this->type->initialize();

        $this->assertSame('type', $this->type->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->type->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->type->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->type->setId($value);
        $this->assertEquals($value, $this->type->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->type->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->type->setUuid($value);
        $this->assertEquals($value, $this->type->getUuid());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->type->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->type->setKey($value);
        $this->assertEquals($value, $this->type->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->type->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->type->setLabel($value);
        $this->assertEquals($value, $this->type->getLabel());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->type->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->type->setPosition($value);
        $this->assertEquals($value, $this->type->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->type->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->type->setDeleted($value);
        $this->assertEquals($value, $this->type->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->type->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->type->setCreatedAt($value);
        $this->assertEquals($value, $this->type->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->type->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->type->setCreatedBy($value);
        $this->assertEquals($value, $this->type->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->type->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->type->setUpdatedAt($value);
        $this->assertEquals($value, $this->type->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->type->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->type->setUpdatedBy($value);
        $this->assertEquals($value, $this->type->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->type->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->type->setDeletedAt($value);
        $this->assertEquals($value, $this->type->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->type->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->type->setDeletedBy($value);
        $this->assertEquals($value, $this->type->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->type->getColumnMap());
    }
}
