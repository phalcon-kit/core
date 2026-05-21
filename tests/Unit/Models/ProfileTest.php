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

use PhalconKit\Models\Abstracts\ProfileAbstract;
use PhalconKit\Models\Abstracts\Interfaces\ProfileAbstractInterface;
use PhalconKit\Models\Profile;
use PhalconKit\Models\Interfaces\ProfileInterface;

/**
 * Class ProfileTest
 *
 * This class contains unit tests for the User class.
 */
class ProfileTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public ProfileInterface $profile;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->profile = new Profile();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Profile::class, $this->profile);
        $this->assertInstanceOf(ProfileInterface::class, $this->profile);
    
        // Abstract
        $this->assertInstanceOf(ProfileAbstract::class, $this->profile);
        $this->assertInstanceOf(ProfileAbstractInterface::class, $this->profile);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->profile);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->profile);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->profile);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->profile);
    }

    public function testInitialize(): void
    {
        $this->profile->initialize();

        $this->assertSame('profile', $this->profile->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->profile->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->profile->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->profile->setId($value);
        $this->assertEquals($value, $this->profile->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->profile->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->profile->setUuid($value);
        $this->assertEquals($value, $this->profile->getUuid());
    }

    public function testGetUserId(): void
    {
        $this->assertEquals(null, $this->profile->getUserId());
    }
    
    public function testSetUserId(): void
    {
        $value = uniqid();
        $this->profile->setUserId($value);
        $this->assertEquals($value, $this->profile->getUserId());
    }

    public function testGetFirstName(): void
    {
        $this->assertEquals(null, $this->profile->getFirstName());
    }
    
    public function testSetFirstName(): void
    {
        $value = uniqid();
        $this->profile->setFirstName($value);
        $this->assertEquals($value, $this->profile->getFirstName());
    }

    public function testGetLastName(): void
    {
        $this->assertEquals(null, $this->profile->getLastName());
    }
    
    public function testSetLastName(): void
    {
        $value = uniqid();
        $this->profile->setLastName($value);
        $this->assertEquals($value, $this->profile->getLastName());
    }

    public function testGetAvatarFileId(): void
    {
        $this->assertEquals(null, $this->profile->getAvatarFileId());
    }
    
    public function testSetAvatarFileId(): void
    {
        $value = uniqid();
        $this->profile->setAvatarFileId($value);
        $this->assertEquals($value, $this->profile->getAvatarFileId());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->profile->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->profile->setDeleted($value);
        $this->assertEquals($value, $this->profile->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->profile->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->profile->setCreatedAt($value);
        $this->assertEquals($value, $this->profile->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->profile->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->profile->setCreatedBy($value);
        $this->assertEquals($value, $this->profile->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->profile->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->profile->setUpdatedAt($value);
        $this->assertEquals($value, $this->profile->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->profile->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->profile->setUpdatedBy($value);
        $this->assertEquals($value, $this->profile->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->profile->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->profile->setDeletedAt($value);
        $this->assertEquals($value, $this->profile->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->profile->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->profile->setDeletedBy($value);
        $this->assertEquals($value, $this->profile->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->profile->getColumnMap());
    }
}
