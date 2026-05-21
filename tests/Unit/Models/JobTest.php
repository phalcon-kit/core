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

use PhalconKit\Models\Abstracts\JobAbstract;
use PhalconKit\Models\Abstracts\Interfaces\JobAbstractInterface;
use PhalconKit\Models\Job;
use PhalconKit\Models\Interfaces\JobInterface;

/**
 * Class JobTest
 *
 * This class contains unit tests for the User class.
 */
class JobTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public JobInterface $job;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->job = new Job();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(Job::class, $this->job);
        $this->assertInstanceOf(JobInterface::class, $this->job);
    
        // Abstract
        $this->assertInstanceOf(JobAbstract::class, $this->job);
        $this->assertInstanceOf(JobAbstractInterface::class, $this->job);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->job);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->job);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->job);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->job);
    }

    public function testInitialize(): void
    {
        $this->job->initialize();

        $this->assertSame('job', $this->job->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->job->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->job->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->job->setId($value);
        $this->assertEquals($value, $this->job->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->job->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->job->setUuid($value);
        $this->assertEquals($value, $this->job->getUuid());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->job->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->job->setLabel($value);
        $this->assertEquals($value, $this->job->getLabel());
    }

    public function testGetTask(): void
    {
        $this->assertEquals(null, $this->job->getTask());
    }
    
    public function testSetTask(): void
    {
        $value = uniqid();
        $this->job->setTask($value);
        $this->assertEquals($value, $this->job->getTask());
    }

    public function testGetAction(): void
    {
        $this->assertEquals(null, $this->job->getAction());
    }
    
    public function testSetAction(): void
    {
        $value = uniqid();
        $this->job->setAction($value);
        $this->assertEquals($value, $this->job->getAction());
    }

    public function testGetParams(): void
    {
        $this->assertEquals(null, $this->job->getParams());
    }
    
    public function testSetParams(): void
    {
        $value = uniqid();
        $this->job->setParams($value);
        $this->assertEquals($value, $this->job->getParams());
    }

    public function testGetStatus(): void
    {
        $this->assertEquals('\'new\'', $this->job->getStatus());
    }
    
    public function testSetStatus(): void
    {
        $value = uniqid();
        $this->job->setStatus($value);
        $this->assertEquals($value, $this->job->getStatus());
    }

    public function testGetResult(): void
    {
        $this->assertEquals(null, $this->job->getResult());
    }
    
    public function testSetResult(): void
    {
        $value = uniqid();
        $this->job->setResult($value);
        $this->assertEquals($value, $this->job->getResult());
    }

    public function testGetPriority(): void
    {
        $this->assertEquals(null, $this->job->getPriority());
    }
    
    public function testSetPriority(): void
    {
        $value = uniqid();
        $this->job->setPriority($value);
        $this->assertEquals($value, $this->job->getPriority());
    }

    public function testGetRunAt(): void
    {
        $this->assertEquals(null, $this->job->getRunAt());
    }
    
    public function testSetRunAt(): void
    {
        $value = uniqid();
        $this->job->setRunAt($value);
        $this->assertEquals($value, $this->job->getRunAt());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->job->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->job->setDeleted($value);
        $this->assertEquals($value, $this->job->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->job->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->job->setCreatedAt($value);
        $this->assertEquals($value, $this->job->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->job->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->job->setCreatedBy($value);
        $this->assertEquals($value, $this->job->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals(null, $this->job->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->job->setUpdatedAt($value);
        $this->assertEquals($value, $this->job->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->job->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->job->setUpdatedBy($value);
        $this->assertEquals($value, $this->job->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals(null, $this->job->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->job->setDeletedAt($value);
        $this->assertEquals($value, $this->job->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->job->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->job->setDeletedBy($value);
        $this->assertEquals($value, $this->job->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->job->getColumnMap());
    }
}
