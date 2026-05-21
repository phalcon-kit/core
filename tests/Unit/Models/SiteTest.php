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

use PhalconKit\Models\Abstracts\SiteAbstract;
use PhalconKit\Models\Abstracts\Interfaces\SiteAbstractInterface;
use PhalconKit\Models\Site;
use PhalconKit\Models\Interfaces\SiteInterface;

/**
 * Class SiteTest
 *
 * This class contains unit tests for the User class.
 */
class SiteTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public SiteInterface $site;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->site = new Site();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Site::class, $this->site);
        $this->assertInstanceOf(SiteInterface::class, $this->site);
    
        // Abstract
        $this->assertInstanceOf(SiteAbstract::class, $this->site);
        $this->assertInstanceOf(SiteAbstractInterface::class, $this->site);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->site);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->site);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->site);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->site);
    }

    public function testInitialize(): void
    {
        $this->site->initialize();

        $this->assertSame('site', $this->site->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->site->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->site->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->site->setId($value);
        $this->assertEquals($value, $this->site->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->site->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->site->setUuid($value);
        $this->assertEquals($value, $this->site->getUuid());
    }

    public function testGetWorkspaceId(): void
    {
        $this->assertEquals(null, $this->site->getWorkspaceId());
    }
    
    public function testSetWorkspaceId(): void
    {
        $value = uniqid();
        $this->site->setWorkspaceId($value);
        $this->assertEquals($value, $this->site->getWorkspaceId());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->site->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->site->setLabel($value);
        $this->assertEquals($value, $this->site->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals(null, $this->site->getDescription());
    }
    
    public function testSetDescription(): void
    {
        $value = uniqid();
        $this->site->setDescription($value);
        $this->assertEquals($value, $this->site->getDescription());
    }

    public function testGetIcon(): void
    {
        $this->assertEquals(null, $this->site->getIcon());
    }
    
    public function testSetIcon(): void
    {
        $value = uniqid();
        $this->site->setIcon($value);
        $this->assertEquals($value, $this->site->getIcon());
    }

    public function testGetColor(): void
    {
        $this->assertEquals(null, $this->site->getColor());
    }
    
    public function testSetColor(): void
    {
        $value = uniqid();
        $this->site->setColor($value);
        $this->assertEquals($value, $this->site->getColor());
    }

    public function testGetStatus(): void
    {
        $this->assertEquals('\'active\'', $this->site->getStatus());
    }
    
    public function testSetStatus(): void
    {
        $value = uniqid();
        $this->site->setStatus($value);
        $this->assertEquals($value, $this->site->getStatus());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->site->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->site->setDeleted($value);
        $this->assertEquals($value, $this->site->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->site->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->site->setCreatedAt($value);
        $this->assertEquals($value, $this->site->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->site->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->site->setCreatedBy($value);
        $this->assertEquals($value, $this->site->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->site->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->site->setUpdatedAt($value);
        $this->assertEquals($value, $this->site->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->site->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->site->setUpdatedBy($value);
        $this->assertEquals($value, $this->site->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->site->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->site->setDeletedAt($value);
        $this->assertEquals($value, $this->site->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->site->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->site->setDeletedBy($value);
        $this->assertEquals($value, $this->site->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->site->getColumnMap());
    }
}
