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

use PhalconKit\Models\Abstracts\UserAbstract;
use PhalconKit\Models\Abstracts\Interfaces\UserAbstractInterface;
use PhalconKit\Models\User;
use PhalconKit\Models\Interfaces\UserInterface;

/**
 * Class UserTest
 *
 * This class contains unit tests for the User class.
 */
class UserTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public UserInterface $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(User::class, $this->user);
        $this->assertInstanceOf(UserInterface::class, $this->user);
    
        // Abstract
        $this->assertInstanceOf(UserAbstract::class, $this->user);
        $this->assertInstanceOf(UserAbstractInterface::class, $this->user);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->user);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->user);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->user);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->user);
    }

    public function testInitialize(): void
    {
        $this->user->initialize();

        $this->assertSame('user', $this->user->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->user->validation());
    }

    public function testSavePreparationHashesPasswordsAndPreservesExistingHashes(): void
    {
        $user = new User();
        $user->setPassword('A private example password!');
        $this->assertTrue($user->beforeSave());
        $hash = $user->getPassword();
        $this->assertIsString($hash);
        $this->assertNotSame('A private example password!', $hash);
        $this->assertTrue($user->checkHash($hash, 'A private example password!'));
        $this->assertFalse($user->checkHash($hash, 'Wrong password!'));

        $user->setEmail('updated@example.test');
        $user->beforeSave();
        $this->assertSame($hash, $user->getPassword());

        $user->setPassword('A replacement example password!');
        $user->beforeSave();
        $this->assertTrue($user->checkHash($user->getPassword(), 'A replacement example password!'));
        $this->assertFalse($user->checkHash($user->getPassword(), 'A private example password!'));
    }

    public function testSavePreparationPreservesDisabledPasswordLogin(): void
    {
        $user = new User();
        foreach ([null, ''] as $password) {
            $user->setPassword($password);
            $user->beforeSave();
            $this->assertSame($password, $user->getPassword());
        }
    }

    public function testSavePreparationPreservesConfiguredLegacyHashes(): void
    {
        $security = $this->di->getShared('security');
        $originalAlgorithm = $security->getDefaultHash();
        $algorithms = [
            \Phalcon\Encryption\Security::CRYPT_BLOWFISH_A,
            \Phalcon\Encryption\Security::CRYPT_BLOWFISH_X,
            \Phalcon\Encryption\Security::CRYPT_MD5,
            \Phalcon\Encryption\Security::CRYPT_SHA256,
            \Phalcon\Encryption\Security::CRYPT_SHA512,
        ];

        try {
            foreach ($algorithms as $algorithm) {
                $security->setDefaultHash($algorithm);
                $user = new User();
                $user->setPassword('A private legacy algorithm password!');
                $user->beforeSave();
                $hash = $user->getPassword();
                $this->assertTrue($user->checkHash($hash, 'A private legacy algorithm password!'));

                $user->setEmail('updated@example.test');
                $user->beforeSave();
                $this->assertSame($hash, $user->getPassword());
                $this->assertTrue($user->checkHash($user->getPassword(), 'A private legacy algorithm password!'));

                $importedUser = new User();
                $importedUser->setPassword($hash);
                $importedUser->beforeSave();
                $this->assertSame($hash, $importedUser->getPassword());
            }
        } finally {
            $security->setDefaultHash($originalAlgorithm);
        }
    }

    public function testSavePreparationHashesPlaintextWithLegacyHashPrefixes(): void
    {
        foreach (['$2a$not-a-hash', '$2x$not-a-hash', '$1$not-a-hash', '$5$not-a-hash', '$6$not-a-hash'] as $password) {
            $user = new User();
            $user->setPassword($password);
            $user->beforeSave();
            $this->assertNotSame($password, $user->getPassword());
            $this->assertTrue($user->checkHash($user->getPassword(), $password));
        }
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->user->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->user->setId($value);
        $this->assertEquals($value, $this->user->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->user->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->user->setUuid($value);
        $this->assertEquals($value, $this->user->getUuid());
    }

    public function testGetEmail(): void
    {
        $this->assertEquals(null, $this->user->getEmail());
    }
    
    public function testSetEmail(): void
    {
        $value = uniqid();
        $this->user->setEmail($value);
        $this->assertEquals($value, $this->user->getEmail());
    }

    public function testGetPassword(): void
    {
        $this->assertEquals(null, $this->user->getPassword());
    }
    
    public function testSetPassword(): void
    {
        $value = uniqid();
        $this->user->setPassword($value);
        $this->assertEquals($value, $this->user->getPassword());
    }

    public function testGetResetToken(): void
    {
        $this->assertEquals(null, $this->user->getResetToken());
    }
    
    public function testSetResetToken(): void
    {
        $value = uniqid();
        $this->user->setResetToken($value);
        $this->assertEquals($value, $this->user->getResetToken());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->user->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->user->setDeleted($value);
        $this->assertEquals($value, $this->user->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->user->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->user->setCreatedAt($value);
        $this->assertEquals($value, $this->user->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->user->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->user->setCreatedBy($value);
        $this->assertEquals($value, $this->user->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->user->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->user->setUpdatedAt($value);
        $this->assertEquals($value, $this->user->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->user->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->user->setUpdatedBy($value);
        $this->assertEquals($value, $this->user->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->user->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->user->setDeletedAt($value);
        $this->assertEquals($value, $this->user->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->user->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->user->setDeletedBy($value);
        $this->assertEquals($value, $this->user->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->user->getColumnMap());
    }
}
