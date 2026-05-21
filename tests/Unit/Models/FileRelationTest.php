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

use PhalconKit\Models\Abstracts\FileRelationAbstract;
use PhalconKit\Models\Abstracts\Interfaces\FileRelationAbstractInterface;
use PhalconKit\Models\FileRelation;
use PhalconKit\Models\Interfaces\FileRelationInterface;

/**
 * Class FileRelationTest
 *
 * This class contains unit tests for the User class.
 */
class FileRelationTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public FileRelationInterface $fileRelation;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->fileRelation = new FileRelation();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(FileRelation::class, $this->fileRelation);
        $this->assertInstanceOf(FileRelationInterface::class, $this->fileRelation);
    
        // Abstract
        $this->assertInstanceOf(FileRelationAbstract::class, $this->fileRelation);
        $this->assertInstanceOf(FileRelationAbstractInterface::class, $this->fileRelation);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->fileRelation);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->fileRelation);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->fileRelation);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->fileRelation);
    }

    public function testInitialize(): void
    {
        $this->fileRelation->initialize();

        $this->assertSame('file_relation', $this->fileRelation->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->fileRelation->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->fileRelation->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->fileRelation->setId($value);
        $this->assertEquals($value, $this->fileRelation->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->fileRelation->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->fileRelation->setUuid($value);
        $this->assertEquals($value, $this->fileRelation->getUuid());
    }

    public function testGetFileId(): void
    {
        $this->assertEquals(null, $this->fileRelation->getFileId());
    }
    
    public function testSetFileId(): void
    {
        $value = uniqid();
        $this->fileRelation->setFileId($value);
        $this->assertEquals($value, $this->fileRelation->getFileId());
    }

    public function testGetRelationTable(): void
    {
        $this->assertEquals(null, $this->fileRelation->getRelationTable());
    }
    
    public function testSetRelationTable(): void
    {
        $value = uniqid();
        $this->fileRelation->setRelationTable($value);
        $this->assertEquals($value, $this->fileRelation->getRelationTable());
    }

    public function testGetRelationId(): void
    {
        $this->assertEquals(null, $this->fileRelation->getRelationId());
    }
    
    public function testSetRelationId(): void
    {
        $value = uniqid();
        $this->fileRelation->setRelationId($value);
        $this->assertEquals($value, $this->fileRelation->getRelationId());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->fileRelation->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->fileRelation->setDeleted($value);
        $this->assertEquals($value, $this->fileRelation->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->fileRelation->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->fileRelation->setCreatedAt($value);
        $this->assertEquals($value, $this->fileRelation->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->fileRelation->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->fileRelation->setCreatedBy($value);
        $this->assertEquals($value, $this->fileRelation->getCreatedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->fileRelation->getColumnMap());
    }
}
