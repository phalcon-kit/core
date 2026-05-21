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

use PhalconKit\Models\Abstracts\MetaAbstract;
use PhalconKit\Models\Abstracts\Interfaces\MetaAbstractInterface;
use PhalconKit\Models\Meta;
use PhalconKit\Models\Interfaces\MetaInterface;

/**
 * Class MetaTest
 *
 * This class contains unit tests for the User class.
 */
class MetaTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public MetaInterface $meta;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->meta = new Meta();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Meta::class, $this->meta);
        $this->assertInstanceOf(MetaInterface::class, $this->meta);
    
        // Abstract
        $this->assertInstanceOf(MetaAbstract::class, $this->meta);
        $this->assertInstanceOf(MetaAbstractInterface::class, $this->meta);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->meta);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->meta);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->meta);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->meta);
    }

    public function testInitialize(): void
    {
        $this->meta->initialize();

        $this->assertSame('meta', $this->meta->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->meta->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->meta->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->meta->setId($value);
        $this->assertEquals($value, $this->meta->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->meta->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->meta->setUuid($value);
        $this->assertEquals($value, $this->meta->getUuid());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->meta->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->meta->setKey($value);
        $this->assertEquals($value, $this->meta->getKey());
    }

    public function testGetValue(): void
    {
        $this->assertEquals(null, $this->meta->getValue());
    }
    
    public function testSetValue(): void
    {
        $value = uniqid();
        $this->meta->setValue($value);
        $this->assertEquals($value, $this->meta->getValue());
    }

    public function testGetMetaTable(): void
    {
        $this->assertEquals(null, $this->meta->getMetaTable());
    }
    
    public function testSetMetaTable(): void
    {
        $value = uniqid();
        $this->meta->setMetaTable($value);
        $this->assertEquals($value, $this->meta->getMetaTable());
    }

    public function testGetMetaId(): void
    {
        $this->assertEquals(null, $this->meta->getMetaId());
    }
    
    public function testSetMetaId(): void
    {
        $value = uniqid();
        $this->meta->setMetaId($value);
        $this->assertEquals($value, $this->meta->getMetaId());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->meta->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->meta->setDeleted($value);
        $this->assertEquals($value, $this->meta->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->meta->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->meta->setCreatedAt($value);
        $this->assertEquals($value, $this->meta->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->meta->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->meta->setCreatedBy($value);
        $this->assertEquals($value, $this->meta->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->meta->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->meta->setUpdatedAt($value);
        $this->assertEquals($value, $this->meta->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->meta->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->meta->setUpdatedBy($value);
        $this->assertEquals($value, $this->meta->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->meta->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->meta->setDeletedAt($value);
        $this->assertEquals($value, $this->meta->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->meta->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->meta->setDeletedBy($value);
        $this->assertEquals($value, $this->meta->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->meta->getColumnMap());
    }
}
