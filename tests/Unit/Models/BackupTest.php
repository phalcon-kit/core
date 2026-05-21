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

use PhalconKit\Models\Abstracts\BackupAbstract;
use PhalconKit\Models\Abstracts\Interfaces\BackupAbstractInterface;
use PhalconKit\Models\Backup;
use PhalconKit\Models\Interfaces\BackupInterface;

/**
 * Class BackupTest
 *
 * This class contains unit tests for the User class.
 */
class BackupTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public BackupInterface $backup;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->backup = new Backup();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Backup::class, $this->backup);
        $this->assertInstanceOf(BackupInterface::class, $this->backup);
    
        // Abstract
        $this->assertInstanceOf(BackupAbstract::class, $this->backup);
        $this->assertInstanceOf(BackupAbstractInterface::class, $this->backup);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->backup);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->backup);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->backup);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->backup);
    }

    public function testInitialize(): void
    {
        $this->backup->initialize();

        $this->assertSame('backup', $this->backup->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->backup->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->backup->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->backup->setId($value);
        $this->assertEquals($value, $this->backup->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->backup->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->backup->setUuid($value);
        $this->assertEquals($value, $this->backup->getUuid());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->backup->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->backup->setLabel($value);
        $this->assertEquals($value, $this->backup->getLabel());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->backup->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->backup->setDeleted($value);
        $this->assertEquals($value, $this->backup->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->backup->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->backup->setCreatedAt($value);
        $this->assertEquals($value, $this->backup->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->backup->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->backup->setCreatedBy($value);
        $this->assertEquals($value, $this->backup->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->backup->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->backup->setUpdatedAt($value);
        $this->assertEquals($value, $this->backup->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->backup->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->backup->setUpdatedBy($value);
        $this->assertEquals($value, $this->backup->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->backup->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->backup->setDeletedAt($value);
        $this->assertEquals($value, $this->backup->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->backup->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->backup->setDeletedBy($value);
        $this->assertEquals($value, $this->backup->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->backup->getColumnMap());
    }
}
