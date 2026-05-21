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

use PhalconKit\Models\Abstracts\DataAbstract;
use PhalconKit\Models\Abstracts\Interfaces\DataAbstractInterface;
use PhalconKit\Models\Data;
use PhalconKit\Models\Interfaces\DataInterface;

/**
 * Class DataTest
 *
 * This class contains unit tests for the User class.
 */
class DataTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public DataInterface $data;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->data = new Data();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Data::class, $this->data);
        $this->assertInstanceOf(DataInterface::class, $this->data);
    
        // Abstract
        $this->assertInstanceOf(DataAbstract::class, $this->data);
        $this->assertInstanceOf(DataAbstractInterface::class, $this->data);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->data);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->data);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->data);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->data);
    }

    public function testInitialize(): void
    {
        $this->data->initialize();

        $this->assertSame('data', $this->data->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->data->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->data->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->data->setId($value);
        $this->assertEquals($value, $this->data->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->data->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->data->setUuid($value);
        $this->assertEquals($value, $this->data->getUuid());
    }

    public function testGetTableId(): void
    {
        $this->assertEquals(null, $this->data->getTableId());
    }
    
    public function testSetTableId(): void
    {
        $value = uniqid();
        $this->data->setTableId($value);
        $this->assertEquals($value, $this->data->getTableId());
    }

    public function testGetRecordId(): void
    {
        $this->assertEquals(null, $this->data->getRecordId());
    }
    
    public function testSetRecordId(): void
    {
        $value = uniqid();
        $this->data->setRecordId($value);
        $this->assertEquals($value, $this->data->getRecordId());
    }

    public function testGetColumnId(): void
    {
        $this->assertEquals(null, $this->data->getColumnId());
    }
    
    public function testSetColumnId(): void
    {
        $value = uniqid();
        $this->data->setColumnId($value);
        $this->assertEquals($value, $this->data->getColumnId());
    }

    public function testGetValue(): void
    {
        $this->assertEquals('NULL', $this->data->getValue());
    }
    
    public function testSetValue(): void
    {
        $value = uniqid();
        $this->data->setValue($value);
        $this->assertEquals($value, $this->data->getValue());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->data->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->data->setDeleted($value);
        $this->assertEquals($value, $this->data->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->data->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->data->setCreatedAt($value);
        $this->assertEquals($value, $this->data->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->data->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->data->setCreatedBy($value);
        $this->assertEquals($value, $this->data->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->data->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->data->setUpdatedAt($value);
        $this->assertEquals($value, $this->data->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->data->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->data->setUpdatedBy($value);
        $this->assertEquals($value, $this->data->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->data->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->data->setDeletedAt($value);
        $this->assertEquals($value, $this->data->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->data->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->data->setDeletedBy($value);
        $this->assertEquals($value, $this->data->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->data->getColumnMap());
    }
}
