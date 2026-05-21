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

use PhalconKit\Models\Abstracts\LangAbstract;
use PhalconKit\Models\Abstracts\Interfaces\LangAbstractInterface;
use PhalconKit\Models\Lang;
use PhalconKit\Models\Interfaces\LangInterface;

/**
 * Class LangTest
 *
 * This class contains unit tests for the User class.
 */
class LangTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public LangInterface $lang;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->lang = new Lang();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Lang::class, $this->lang);
        $this->assertInstanceOf(LangInterface::class, $this->lang);
    
        // Abstract
        $this->assertInstanceOf(LangAbstract::class, $this->lang);
        $this->assertInstanceOf(LangAbstractInterface::class, $this->lang);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->lang);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->lang);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->lang);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->lang);
    }

    public function testInitialize(): void
    {
        $this->lang->initialize();

        $this->assertSame('lang', $this->lang->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->lang->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->lang->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->lang->setId($value);
        $this->assertEquals($value, $this->lang->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->lang->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->lang->setUuid($value);
        $this->assertEquals($value, $this->lang->getUuid());
    }

    public function testGetCode(): void
    {
        $this->assertEquals(null, $this->lang->getCode());
    }
    
    public function testSetCode(): void
    {
        $value = uniqid();
        $this->lang->setCode($value);
        $this->assertEquals($value, $this->lang->getCode());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->lang->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->lang->setLabel($value);
        $this->assertEquals($value, $this->lang->getLabel());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->lang->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->lang->setDeleted($value);
        $this->assertEquals($value, $this->lang->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->lang->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->lang->setCreatedAt($value);
        $this->assertEquals($value, $this->lang->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->lang->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->lang->setCreatedBy($value);
        $this->assertEquals($value, $this->lang->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->lang->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->lang->setUpdatedAt($value);
        $this->assertEquals($value, $this->lang->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->lang->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->lang->setUpdatedBy($value);
        $this->assertEquals($value, $this->lang->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->lang->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->lang->setDeletedAt($value);
        $this->assertEquals($value, $this->lang->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->lang->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->lang->setDeletedBy($value);
        $this->assertEquals($value, $this->lang->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->lang->getColumnMap());
    }
}
