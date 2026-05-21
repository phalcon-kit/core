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

use PhalconKit\Models\Abstracts\Oauth2Abstract;
use PhalconKit\Models\Abstracts\Interfaces\Oauth2AbstractInterface;
use PhalconKit\Models\Oauth2;
use PhalconKit\Models\Interfaces\Oauth2Interface;

/**
 * Class Oauth2Test
 *
 * This class contains unit tests for the User class.
 */
class Oauth2Test extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public Oauth2Interface $oauth2;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->oauth2 = new Oauth2();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Oauth2::class, $this->oauth2);
        $this->assertInstanceOf(Oauth2Interface::class, $this->oauth2);
    
        // Abstract
        $this->assertInstanceOf(Oauth2Abstract::class, $this->oauth2);
        $this->assertInstanceOf(Oauth2AbstractInterface::class, $this->oauth2);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->oauth2);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->oauth2);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->oauth2);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->oauth2);
    }

    public function testInitialize(): void
    {
        $this->oauth2->initialize();

        $this->assertSame('oauth2', $this->oauth2->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->oauth2->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->oauth2->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->oauth2->setId($value);
        $this->assertEquals($value, $this->oauth2->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->oauth2->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->oauth2->setUuid($value);
        $this->assertEquals($value, $this->oauth2->getUuid());
    }

    public function testGetUserId(): void
    {
        $this->assertEquals(null, $this->oauth2->getUserId());
    }
    
    public function testSetUserId(): void
    {
        $value = uniqid();
        $this->oauth2->setUserId($value);
        $this->assertEquals($value, $this->oauth2->getUserId());
    }

    public function testGetProvider(): void
    {
        $this->assertEquals(null, $this->oauth2->getProvider());
    }
    
    public function testSetProvider(): void
    {
        $value = uniqid();
        $this->oauth2->setProvider($value);
        $this->assertEquals($value, $this->oauth2->getProvider());
    }

    public function testGetProviderUuid(): void
    {
        $this->assertEquals(null, $this->oauth2->getProviderUuid());
    }
    
    public function testSetProviderUuid(): void
    {
        $value = uniqid();
        $this->oauth2->setProviderUuid($value);
        $this->assertEquals($value, $this->oauth2->getProviderUuid());
    }

    public function testGetAccessToken(): void
    {
        $this->assertEquals(null, $this->oauth2->getAccessToken());
    }
    
    public function testSetAccessToken(): void
    {
        $value = uniqid();
        $this->oauth2->setAccessToken($value);
        $this->assertEquals($value, $this->oauth2->getAccessToken());
    }

    public function testGetRefreshToken(): void
    {
        $this->assertEquals(null, $this->oauth2->getRefreshToken());
    }
    
    public function testSetRefreshToken(): void
    {
        $value = uniqid();
        $this->oauth2->setRefreshToken($value);
        $this->assertEquals($value, $this->oauth2->getRefreshToken());
    }

    public function testGetEmail(): void
    {
        $this->assertEquals(null, $this->oauth2->getEmail());
    }
    
    public function testSetEmail(): void
    {
        $value = uniqid();
        $this->oauth2->setEmail($value);
        $this->assertEquals($value, $this->oauth2->getEmail());
    }

    public function testGetMeta(): void
    {
        $this->assertEquals(null, $this->oauth2->getMeta());
    }
    
    public function testSetMeta(): void
    {
        $value = uniqid();
        $this->oauth2->setMeta($value);
        $this->assertEquals($value, $this->oauth2->getMeta());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->oauth2->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->oauth2->setDeleted($value);
        $this->assertEquals($value, $this->oauth2->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->oauth2->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->oauth2->setCreatedAt($value);
        $this->assertEquals($value, $this->oauth2->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->oauth2->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->oauth2->setCreatedBy($value);
        $this->assertEquals($value, $this->oauth2->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->oauth2->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->oauth2->setUpdatedAt($value);
        $this->assertEquals($value, $this->oauth2->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->oauth2->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->oauth2->setUpdatedBy($value);
        $this->assertEquals($value, $this->oauth2->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->oauth2->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->oauth2->setDeletedAt($value);
        $this->assertEquals($value, $this->oauth2->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->oauth2->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->oauth2->setDeletedBy($value);
        $this->assertEquals($value, $this->oauth2->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->oauth2->getColumnMap());
    }
}
