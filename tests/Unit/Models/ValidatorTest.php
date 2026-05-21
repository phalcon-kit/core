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

use PhalconKit\Models\Abstracts\ValidatorAbstract;
use PhalconKit\Models\Abstracts\Interfaces\ValidatorAbstractInterface;
use PhalconKit\Models\Validator;
use PhalconKit\Models\Interfaces\ValidatorInterface;

/**
 * Class ValidatorTest
 *
 * This class contains unit tests for the User class.
 */
class ValidatorTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public ValidatorInterface $validator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Validator::class, $this->validator);
        $this->assertInstanceOf(ValidatorInterface::class, $this->validator);
    
        // Abstract
        $this->assertInstanceOf(ValidatorAbstract::class, $this->validator);
        $this->assertInstanceOf(ValidatorAbstractInterface::class, $this->validator);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->validator);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->validator);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->validator);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->validator);
    }

    public function testInitialize(): void
    {
        $this->validator->initialize();

        $this->assertSame('validator', $this->validator->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->validator->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->validator->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->validator->setId($value);
        $this->assertEquals($value, $this->validator->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->validator->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->validator->setUuid($value);
        $this->assertEquals($value, $this->validator->getUuid());
    }

    public function testGetColumnId(): void
    {
        $this->assertEquals(null, $this->validator->getColumnId());
    }
    
    public function testSetColumnId(): void
    {
        $value = uniqid();
        $this->validator->setColumnId($value);
        $this->assertEquals($value, $this->validator->getColumnId());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->validator->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->validator->setKey($value);
        $this->assertEquals($value, $this->validator->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->validator->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->validator->setLabel($value);
        $this->assertEquals($value, $this->validator->getLabel());
    }

    public function testGetType(): void
    {
        $this->assertEquals('\'text\'', $this->validator->getType());
    }
    
    public function testSetType(): void
    {
        $value = uniqid();
        $this->validator->setType($value);
        $this->assertEquals($value, $this->validator->getType());
    }

    public function testGetParams(): void
    {
        $this->assertEquals(null, $this->validator->getParams());
    }
    
    public function testSetParams(): void
    {
        $value = uniqid();
        $this->validator->setParams($value);
        $this->assertEquals($value, $this->validator->getParams());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->validator->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->validator->setDeleted($value);
        $this->assertEquals($value, $this->validator->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->validator->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->validator->setCreatedAt($value);
        $this->assertEquals($value, $this->validator->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->validator->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->validator->setCreatedBy($value);
        $this->assertEquals($value, $this->validator->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->validator->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->validator->setUpdatedAt($value);
        $this->assertEquals($value, $this->validator->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->validator->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->validator->setUpdatedBy($value);
        $this->assertEquals($value, $this->validator->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->validator->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->validator->setDeletedAt($value);
        $this->assertEquals($value, $this->validator->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->validator->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->validator->setDeletedBy($value);
        $this->assertEquals($value, $this->validator->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->validator->getColumnMap());
    }
}
