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

use PhalconKit\Models\Abstracts\PhalconMigrationsAbstract;
use PhalconKit\Models\Abstracts\Interfaces\PhalconMigrationsAbstractInterface;
use PhalconKit\Models\PhalconMigrations;
use PhalconKit\Models\Interfaces\PhalconMigrationsInterface;

/**
 * Class PhalconMigrationsTest
 *
 * This class contains unit tests for the User class.
 */
class PhalconMigrationsTest extends \PhalconKit\Tests\Unit\AbstractUnit
{
    public PhalconMigrationsInterface $phalconMigrations;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->phalconMigrations = new PhalconMigrations();
    }
    
    public function testInstanceOf(): void
    {
        // Model
        $this->assertInstanceOf(PhalconMigrations::class, $this->phalconMigrations);
        $this->assertInstanceOf(PhalconMigrationsInterface::class, $this->phalconMigrations);
    
        // Abstract
        $this->assertInstanceOf(PhalconMigrationsAbstract::class, $this->phalconMigrations);
        $this->assertInstanceOf(PhalconMigrationsAbstractInterface::class, $this->phalconMigrations);
        
        // Phalcon Kit
        $this->assertInstanceOf(\PhalconKit\Mvc\ModelInterface::class, $this->phalconMigrations);
        $this->assertInstanceOf(\PhalconKit\Mvc\Model::class, $this->phalconMigrations);
        
        // Phalcon
        $this->assertInstanceOf(\Phalcon\Mvc\ModelInterface::class, $this->phalconMigrations);
        $this->assertInstanceOf(\Phalcon\Mvc\Model::class, $this->phalconMigrations);
    }

    public function testInitialize(): void
    {
        $this->phalconMigrations->initialize();

        $this->assertSame('phalcon_migrations', $this->phalconMigrations->getSource());
    }

    public function testValidationShouldReturnABoolean(): void
    {
        $this->assertIsBool($this->phalconMigrations->validation());
    }
    
    public function testGetVersion(): void
    {
        $this->assertEquals(null, $this->phalconMigrations->getVersion());
    }
    
    public function testSetVersion(): void
    {
        $value = uniqid();
        $this->phalconMigrations->setVersion($value);
        $this->assertEquals($value, $this->phalconMigrations->getVersion());
    }

    public function testGetStartTime(): void
    {
        $this->assertEquals(null, $this->phalconMigrations->getStartTime());
    }
    
    public function testSetStartTime(): void
    {
        $value = uniqid();
        $this->phalconMigrations->setStartTime($value);
        $this->assertEquals($value, $this->phalconMigrations->getStartTime());
    }

    public function testGetEndTime(): void
    {
        $this->assertEquals(null, $this->phalconMigrations->getEndTime());
    }
    
    public function testSetEndTime(): void
    {
        $value = uniqid();
        $this->phalconMigrations->setEndTime($value);
        $this->assertEquals($value, $this->phalconMigrations->getEndTime());
    }
    
    public function testGetColumnMapShouldBeAnArray(): void
    {
        $this->assertIsArray($this->phalconMigrations->getColumnMap());
    }
}
