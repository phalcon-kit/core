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

use PhalconKit\Models\Abstracts\CategoryAbstract;
use PhalconKit\Models\Abstracts\Interfaces\CategoryAbstractInterface;
use PhalconKit\Models\Category;
use PhalconKit\Models\Interfaces\CategoryInterface;

/**
 * Class CategoryTest
 *
 * This class contains unit tests for the User class.
 */
class CategoryTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public CategoryInterface $category;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->category = new Category();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Category::class, $this->category);
        $this->assertInstanceOf(CategoryInterface::class, $this->category);
    
        // Abstract
        $this->assertInstanceOf(CategoryAbstract::class, $this->category);
        $this->assertInstanceOf(CategoryAbstractInterface::class, $this->category);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->category);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->category);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->category);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->category);
    }

    public function testInitialize(): void
    {
        $this->category->initialize();

        $this->assertSame('category', $this->category->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->category->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->category->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->category->setId($value);
        $this->assertEquals($value, $this->category->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->category->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->category->setUuid($value);
        $this->assertEquals($value, $this->category->getUuid());
    }

    public function testGetSiteId(): void
    {
        $this->assertEquals(null, $this->category->getSiteId());
    }
    
    public function testSetSiteId(): void
    {
        $value = uniqid();
        $this->category->setSiteId($value);
        $this->assertEquals($value, $this->category->getSiteId());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->category->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->category->setKey($value);
        $this->assertEquals($value, $this->category->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->category->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->category->setLabel($value);
        $this->assertEquals($value, $this->category->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals(null, $this->category->getDescription());
    }
    
    public function testSetDescription(): void
    {
        $value = uniqid();
        $this->category->setDescription($value);
        $this->assertEquals($value, $this->category->getDescription());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->category->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->category->setDeleted($value);
        $this->assertEquals($value, $this->category->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->category->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->category->setCreatedAt($value);
        $this->assertEquals($value, $this->category->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->category->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->category->setCreatedBy($value);
        $this->assertEquals($value, $this->category->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->category->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->category->setUpdatedAt($value);
        $this->assertEquals($value, $this->category->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->category->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->category->setUpdatedBy($value);
        $this->assertEquals($value, $this->category->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->category->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->category->setDeletedAt($value);
        $this->assertEquals($value, $this->category->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->category->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->category->setDeletedBy($value);
        $this->assertEquals($value, $this->category->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->category->getColumnMap());
    }
}
