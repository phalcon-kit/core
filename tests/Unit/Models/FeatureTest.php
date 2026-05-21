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

use PhalconKit\Models\Abstracts\FeatureAbstract;
use PhalconKit\Models\Abstracts\Interfaces\FeatureAbstractInterface;
use PhalconKit\Models\Feature;
use PhalconKit\Models\Interfaces\FeatureInterface;

/**
 * Class FeatureTest
 *
 * This class contains unit tests for the User class.
 */
class FeatureTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public FeatureInterface $feature;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->feature = new Feature();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Feature::class, $this->feature);
        $this->assertInstanceOf(FeatureInterface::class, $this->feature);
    
        // Abstract
        $this->assertInstanceOf(FeatureAbstract::class, $this->feature);
        $this->assertInstanceOf(FeatureAbstractInterface::class, $this->feature);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->feature);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->feature);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->feature);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->feature);
    }

    public function testInitialize(): void
    {
        $this->feature->initialize();

        $this->assertSame('feature', $this->feature->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->feature->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->feature->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->feature->setId($value);
        $this->assertEquals($value, $this->feature->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->feature->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->feature->setUuid($value);
        $this->assertEquals($value, $this->feature->getUuid());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->feature->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->feature->setKey($value);
        $this->assertEquals($value, $this->feature->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->feature->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->feature->setLabel($value);
        $this->assertEquals($value, $this->feature->getLabel());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(null, $this->feature->getPosition());
    }
    
    public function testSetPosition(): void
    {
        $value = uniqid();
        $this->feature->setPosition($value);
        $this->assertEquals($value, $this->feature->getPosition());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->feature->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->feature->setDeleted($value);
        $this->assertEquals($value, $this->feature->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->feature->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->feature->setCreatedAt($value);
        $this->assertEquals($value, $this->feature->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->feature->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->feature->setCreatedBy($value);
        $this->assertEquals($value, $this->feature->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->feature->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->feature->setUpdatedAt($value);
        $this->assertEquals($value, $this->feature->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->feature->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->feature->setUpdatedBy($value);
        $this->assertEquals($value, $this->feature->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->feature->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->feature->setDeletedAt($value);
        $this->assertEquals($value, $this->feature->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->feature->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->feature->setDeletedBy($value);
        $this->assertEquals($value, $this->feature->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->feature->getColumnMap());
    }
}
