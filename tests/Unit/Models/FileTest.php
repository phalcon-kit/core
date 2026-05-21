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

use PhalconKit\Models\Abstracts\FileAbstract;
use PhalconKit\Models\Abstracts\Interfaces\FileAbstractInterface;
use PhalconKit\Models\File;
use PhalconKit\Models\Interfaces\FileInterface;

/**
 * Class FileTest
 *
 * This class contains unit tests for the User class.
 */
class FileTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public FileInterface $file;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->file = new File();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(File::class, $this->file);
        $this->assertInstanceOf(FileInterface::class, $this->file);
    
        // Abstract
        $this->assertInstanceOf(FileAbstract::class, $this->file);
        $this->assertInstanceOf(FileAbstractInterface::class, $this->file);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->file);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->file);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->file);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->file);
    }

    public function testInitialize(): void
    {
        $this->file->initialize();

        $this->assertSame('file', $this->file->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->file->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->file->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->file->setId($value);
        $this->assertEquals($value, $this->file->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->file->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->file->setUuid($value);
        $this->assertEquals($value, $this->file->getUuid());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->file->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->file->setLabel($value);
        $this->assertEquals($value, $this->file->getLabel());
    }

    public function testGetCategory(): void
    {
        $this->assertEquals('\'other\'', $this->file->getCategory());
    }
    
    public function testSetCategory(): void
    {
        $value = uniqid();
        $this->file->setCategory($value);
        $this->assertEquals($value, $this->file->getCategory());
    }

    public function testGetPath(): void
    {
        $this->assertEquals(null, $this->file->getPath());
    }
    
    public function testSetPath(): void
    {
        $value = uniqid();
        $this->file->setPath($value);
        $this->assertEquals($value, $this->file->getPath());
    }

    public function testGetMimeType(): void
    {
        $this->assertEquals(null, $this->file->getMimeType());
    }
    
    public function testSetMimeType(): void
    {
        $value = uniqid();
        $this->file->setMimeType($value);
        $this->assertEquals($value, $this->file->getMimeType());
    }

    public function testGetExtension(): void
    {
        $this->assertEquals(null, $this->file->getExtension());
    }
    
    public function testSetExtension(): void
    {
        $value = uniqid();
        $this->file->setExtension($value);
        $this->assertEquals($value, $this->file->getExtension());
    }

    public function testGetSize(): void
    {
        $this->assertEquals(null, $this->file->getSize());
    }
    
    public function testSetSize(): void
    {
        $value = uniqid();
        $this->file->setSize($value);
        $this->assertEquals($value, $this->file->getSize());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->file->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->file->setDeleted($value);
        $this->assertEquals($value, $this->file->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->file->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->file->setCreatedAt($value);
        $this->assertEquals($value, $this->file->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->file->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->file->setCreatedBy($value);
        $this->assertEquals($value, $this->file->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->file->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->file->setUpdatedAt($value);
        $this->assertEquals($value, $this->file->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->file->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->file->setUpdatedBy($value);
        $this->assertEquals($value, $this->file->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->file->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->file->setDeletedAt($value);
        $this->assertEquals($value, $this->file->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->file->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->file->setDeletedBy($value);
        $this->assertEquals($value, $this->file->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->file->getColumnMap());
    }
}
