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

use PhalconKit\Models\Abstracts\PostCategoryAbstract;
use PhalconKit\Models\Abstracts\Interfaces\PostCategoryAbstractInterface;
use PhalconKit\Models\PostCategory;
use PhalconKit\Models\Interfaces\PostCategoryInterface;

/**
 * Class PostCategoryTest
 *
 * This class contains unit tests for the User class.
 */
class PostCategoryTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public PostCategoryInterface $postCategory;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->postCategory = new PostCategory();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(PostCategory::class, $this->postCategory);
        $this->assertInstanceOf(PostCategoryInterface::class, $this->postCategory);
    
        // Abstract
        $this->assertInstanceOf(PostCategoryAbstract::class, $this->postCategory);
        $this->assertInstanceOf(PostCategoryAbstractInterface::class, $this->postCategory);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->postCategory);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->postCategory);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->postCategory);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->postCategory);
    }

    public function testInitialize(): void
    {
        $this->postCategory->initialize();

        $this->assertSame('post_category', $this->postCategory->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->postCategory->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->postCategory->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->postCategory->setId($value);
        $this->assertEquals($value, $this->postCategory->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->postCategory->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->postCategory->setUuid($value);
        $this->assertEquals($value, $this->postCategory->getUuid());
    }

    public function testGetPostId(): void
    {
        $this->assertEquals(null, $this->postCategory->getPostId());
    }
    
    public function testSetPostId(): void
    {
        $value = uniqid();
        $this->postCategory->setPostId($value);
        $this->assertEquals($value, $this->postCategory->getPostId());
    }

    public function testGetCategoryId(): void
    {
        $this->assertEquals(null, $this->postCategory->getCategoryId());
    }
    
    public function testSetCategoryId(): void
    {
        $value = uniqid();
        $this->postCategory->setCategoryId($value);
        $this->assertEquals($value, $this->postCategory->getCategoryId());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->postCategory->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->postCategory->setDeleted($value);
        $this->assertEquals($value, $this->postCategory->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->postCategory->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->postCategory->setCreatedAt($value);
        $this->assertEquals($value, $this->postCategory->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->postCategory->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->postCategory->setCreatedBy($value);
        $this->assertEquals($value, $this->postCategory->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->postCategory->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->postCategory->setUpdatedAt($value);
        $this->assertEquals($value, $this->postCategory->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->postCategory->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->postCategory->setUpdatedBy($value);
        $this->assertEquals($value, $this->postCategory->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->postCategory->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->postCategory->setDeletedAt($value);
        $this->assertEquals($value, $this->postCategory->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->postCategory->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->postCategory->setDeletedBy($value);
        $this->assertEquals($value, $this->postCategory->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->postCategory->getColumnMap());
    }
}
