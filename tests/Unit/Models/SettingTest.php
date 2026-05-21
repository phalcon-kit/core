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

use PhalconKit\Models\Abstracts\SettingAbstract;
use PhalconKit\Models\Abstracts\Interfaces\SettingAbstractInterface;
use PhalconKit\Models\Setting;
use PhalconKit\Models\Interfaces\SettingInterface;

/**
 * Class SettingTest
 *
 * This class contains unit tests for the User class.
 */
class SettingTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public SettingInterface $setting;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setting = new Setting();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Setting::class, $this->setting);
        $this->assertInstanceOf(SettingInterface::class, $this->setting);
    
        // Abstract
        $this->assertInstanceOf(SettingAbstract::class, $this->setting);
        $this->assertInstanceOf(SettingAbstractInterface::class, $this->setting);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->setting);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->setting);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->setting);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->setting);
    }

    public function testInitialize(): void
    {
        $this->setting->initialize();

        $this->assertSame('setting', $this->setting->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->setting->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->setting->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->setting->setId($value);
        $this->assertEquals($value, $this->setting->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->setting->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->setting->setUuid($value);
        $this->assertEquals($value, $this->setting->getUuid());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->setting->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->setting->setKey($value);
        $this->assertEquals($value, $this->setting->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->setting->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->setting->setLabel($value);
        $this->assertEquals($value, $this->setting->getLabel());
    }

    public function testGetValue(): void
    {
        $this->assertEquals(null, $this->setting->getValue());
    }
    
    public function testSetValue(): void
    {
        $value = uniqid();
        $this->setting->setValue($value);
        $this->assertEquals($value, $this->setting->getValue());
    }

    public function testGetCategory(): void
    {
        $this->assertEquals(null, $this->setting->getCategory());
    }
    
    public function testSetCategory(): void
    {
        $value = uniqid();
        $this->setting->setCategory($value);
        $this->assertEquals($value, $this->setting->getCategory());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->setting->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->setting->setDeleted($value);
        $this->assertEquals($value, $this->setting->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->setting->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->setting->setCreatedAt($value);
        $this->assertEquals($value, $this->setting->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->setting->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->setting->setCreatedBy($value);
        $this->assertEquals($value, $this->setting->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->setting->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->setting->setUpdatedAt($value);
        $this->assertEquals($value, $this->setting->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->setting->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->setting->setUpdatedBy($value);
        $this->assertEquals($value, $this->setting->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->setting->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->setting->setDeletedAt($value);
        $this->assertEquals($value, $this->setting->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->setting->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->setting->setDeletedBy($value);
        $this->assertEquals($value, $this->setting->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->setting->getColumnMap());
    }
}
