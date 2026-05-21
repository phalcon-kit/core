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

use PhalconKit\Models\Abstracts\RecordAbstract;
use PhalconKit\Models\Abstracts\Interfaces\RecordAbstractInterface;
use PhalconKit\Models\Record;
use PhalconKit\Models\Interfaces\RecordInterface;

/**
 * Class RecordTest
 *
 * This class contains unit tests for the User class.
 */
class RecordTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public RecordInterface $record;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->record = new Record();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Record::class, $this->record);
        $this->assertInstanceOf(RecordInterface::class, $this->record);
    
        // Abstract
        $this->assertInstanceOf(RecordAbstract::class, $this->record);
        $this->assertInstanceOf(RecordAbstractInterface::class, $this->record);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->record);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->record);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->record);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->record);
    }

    public function testInitialize(): void
    {
        $this->record->initialize();

        $this->assertSame('record', $this->record->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->record->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->record->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->record->setId($value);
        $this->assertEquals($value, $this->record->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->record->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->record->setUuid($value);
        $this->assertEquals($value, $this->record->getUuid());
    }

    public function testGetTableId(): void
    {
        $this->assertEquals(null, $this->record->getTableId());
    }
    
    public function testSetTableId(): void
    {
        $value = uniqid();
        $this->record->setTableId($value);
        $this->assertEquals($value, $this->record->getTableId());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->record->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->record->setDeleted($value);
        $this->assertEquals($value, $this->record->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->record->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->record->setCreatedAt($value);
        $this->assertEquals($value, $this->record->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->record->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->record->setCreatedBy($value);
        $this->assertEquals($value, $this->record->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->record->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->record->setUpdatedAt($value);
        $this->assertEquals($value, $this->record->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->record->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->record->setUpdatedBy($value);
        $this->assertEquals($value, $this->record->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->record->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->record->setDeletedAt($value);
        $this->assertEquals($value, $this->record->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->record->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->record->setDeletedBy($value);
        $this->assertEquals($value, $this->record->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->record->getColumnMap());
    }
}
