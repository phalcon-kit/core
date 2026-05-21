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

use PhalconKit\Models\Abstracts\PageAbstract;
use PhalconKit\Models\Abstracts\Interfaces\PageAbstractInterface;
use PhalconKit\Models\Page;
use PhalconKit\Models\Interfaces\PageInterface;

/**
 * Class PageTest
 *
 * This class contains unit tests for the User class.
 */
class PageTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public PageInterface $page;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->page = new Page();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Page::class, $this->page);
        $this->assertInstanceOf(PageInterface::class, $this->page);
    
        // Abstract
        $this->assertInstanceOf(PageAbstract::class, $this->page);
        $this->assertInstanceOf(PageAbstractInterface::class, $this->page);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->page);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->page);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->page);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->page);
    }

    public function testInitialize(): void
    {
        $this->page->initialize();

        $this->assertSame('page', $this->page->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->page->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->page->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->page->setId($value);
        $this->assertEquals($value, $this->page->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->page->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->page->setUuid($value);
        $this->assertEquals($value, $this->page->getUuid());
    }

    public function testGetSiteId(): void
    {
        $this->assertEquals(null, $this->page->getSiteId());
    }
    
    public function testSetSiteId(): void
    {
        $value = uniqid();
        $this->page->setSiteId($value);
        $this->assertEquals($value, $this->page->getSiteId());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->page->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->page->setLabel($value);
        $this->assertEquals($value, $this->page->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals(null, $this->page->getDescription());
    }
    
    public function testSetDescription(): void
    {
        $value = uniqid();
        $this->page->setDescription($value);
        $this->assertEquals($value, $this->page->getDescription());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->page->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->page->setDeleted($value);
        $this->assertEquals($value, $this->page->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->page->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->page->setCreatedAt($value);
        $this->assertEquals($value, $this->page->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->page->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->page->setCreatedBy($value);
        $this->assertEquals($value, $this->page->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->page->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->page->setUpdatedAt($value);
        $this->assertEquals($value, $this->page->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->page->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->page->setUpdatedBy($value);
        $this->assertEquals($value, $this->page->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->page->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->page->setDeletedAt($value);
        $this->assertEquals($value, $this->page->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->page->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->page->setDeletedBy($value);
        $this->assertEquals($value, $this->page->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->page->getColumnMap());
    }
}
