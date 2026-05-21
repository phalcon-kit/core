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

use PhalconKit\Models\Abstracts\JobSchedulerAbstract;
use PhalconKit\Models\Abstracts\Interfaces\JobSchedulerAbstractInterface;
use PhalconKit\Models\JobScheduler;
use PhalconKit\Models\Interfaces\JobSchedulerInterface;

/**
 * Class JobSchedulerTest
 *
 * This class contains unit tests for the User class.
 */
class JobSchedulerTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public JobSchedulerInterface $jobScheduler;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->jobScheduler = new JobScheduler();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(JobScheduler::class, $this->jobScheduler);
        $this->assertInstanceOf(JobSchedulerInterface::class, $this->jobScheduler);
    
        // Abstract
        $this->assertInstanceOf(JobSchedulerAbstract::class, $this->jobScheduler);
        $this->assertInstanceOf(JobSchedulerAbstractInterface::class, $this->jobScheduler);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->jobScheduler);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->jobScheduler);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->jobScheduler);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->jobScheduler);
    }

    public function testInitialize(): void
    {
        $this->jobScheduler->initialize();

        $this->assertSame('job_scheduler', $this->jobScheduler->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->jobScheduler->validation());
    }
    
    public function testGetId(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getId());
    }
    
    public function testSetId(): void
    {
        $value = uniqid();
        $this->jobScheduler->setId($value);
        $this->assertEquals($value, $this->jobScheduler->getId());
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getUuid());
    }
    
    public function testSetUuid(): void
    {
        $value = uniqid();
        $this->jobScheduler->setUuid($value);
        $this->assertEquals($value, $this->jobScheduler->getUuid());
    }

    public function testGetKey(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getKey());
    }
    
    public function testSetKey(): void
    {
        $value = uniqid();
        $this->jobScheduler->setKey($value);
        $this->assertEquals($value, $this->jobScheduler->getKey());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getLabel());
    }
    
    public function testSetLabel(): void
    {
        $value = uniqid();
        $this->jobScheduler->setLabel($value);
        $this->assertEquals($value, $this->jobScheduler->getLabel());
    }

    public function testGetTask(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getTask());
    }
    
    public function testSetTask(): void
    {
        $value = uniqid();
        $this->jobScheduler->setTask($value);
        $this->assertEquals($value, $this->jobScheduler->getTask());
    }

    public function testGetAction(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getAction());
    }
    
    public function testSetAction(): void
    {
        $value = uniqid();
        $this->jobScheduler->setAction($value);
        $this->assertEquals($value, $this->jobScheduler->getAction());
    }

    public function testGetParams(): void
    {
        $this->assertEquals('NULL', $this->jobScheduler->getParams());
    }
    
    public function testSetParams(): void
    {
        $value = uniqid();
        $this->jobScheduler->setParams($value);
        $this->assertEquals($value, $this->jobScheduler->getParams());
    }

    public function testGetFrequency(): void
    {
        $this->assertEquals('\'manually\'', $this->jobScheduler->getFrequency());
    }
    
    public function testSetFrequency(): void
    {
        $value = uniqid();
        $this->jobScheduler->setFrequency($value);
        $this->assertEquals($value, $this->jobScheduler->getFrequency());
    }

    public function testGetStartingAt(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getStartingAt());
    }
    
    public function testSetStartingAt(): void
    {
        $value = uniqid();
        $this->jobScheduler->setStartingAt($value);
        $this->assertEquals($value, $this->jobScheduler->getStartingAt());
    }

    public function testGetDeleted(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getDeleted());
    }
    
    public function testSetDeleted(): void
    {
        $value = uniqid();
        $this->jobScheduler->setDeleted($value);
        $this->assertEquals($value, $this->jobScheduler->getDeleted());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertEquals('current_timestamp()', $this->jobScheduler->getCreatedAt());
    }
    
    public function testSetCreatedAt(): void
    {
        $value = uniqid();
        $this->jobScheduler->setCreatedAt($value);
        $this->assertEquals($value, $this->jobScheduler->getCreatedAt());
    }

    public function testGetCreatedBy(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getCreatedBy());
    }
    
    public function testSetCreatedBy(): void
    {
        $value = uniqid();
        $this->jobScheduler->setCreatedBy($value);
        $this->assertEquals($value, $this->jobScheduler->getCreatedBy());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertEquals('NULL', $this->jobScheduler->getUpdatedAt());
    }
    
    public function testSetUpdatedAt(): void
    {
        $value = uniqid();
        $this->jobScheduler->setUpdatedAt($value);
        $this->assertEquals($value, $this->jobScheduler->getUpdatedAt());
    }

    public function testGetUpdatedBy(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getUpdatedBy());
    }
    
    public function testSetUpdatedBy(): void
    {
        $value = uniqid();
        $this->jobScheduler->setUpdatedBy($value);
        $this->assertEquals($value, $this->jobScheduler->getUpdatedBy());
    }

    public function testGetDeletedAt(): void
    {
        $this->assertEquals('NULL', $this->jobScheduler->getDeletedAt());
    }
    
    public function testSetDeletedAt(): void
    {
        $value = uniqid();
        $this->jobScheduler->setDeletedAt($value);
        $this->assertEquals($value, $this->jobScheduler->getDeletedAt());
    }

    public function testGetDeletedBy(): void
    {
        $this->assertEquals(null, $this->jobScheduler->getDeletedBy());
    }
    
    public function testSetDeletedBy(): void
    {
        $value = uniqid();
        $this->jobScheduler->setDeletedBy($value);
        $this->assertEquals($value, $this->jobScheduler->getDeletedBy());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->jobScheduler->getColumnMap());
    }
}
