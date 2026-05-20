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

namespace PhalconKit\Tests\Unit\Db;

use Phalcon\Db\Column;
use PhalconKit\Bootstrap;
use PhalconKit\Db\Adapter\Pdo\Mysql;
use PhalconKit\Tests\Unit\AbstractUnit;

class ProfilerTest extends AbstractUnit
{
    public \PhalconKit\Db\Profiler $profiler;
    
    protected string $mode = Bootstrap::MODE_CLI;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->profiler = $this->di->get('profiler');
    }
    
    public function testProfilerFromDi(): void
    {
        $this->assertInstanceOf(\Phalcon\Db\Profiler::class, $this->profiler);
        $this->assertInstanceOf(\PhalconKit\Db\Profiler::class, $this->profiler);
    }
    
    public function testProfilerToArray(): void
    {
        $profiles = $this->profiler->toArray();
        $this->assertIsArray($profiles['profiles']);
        $this->assertEquals(0, $profiles['numberTotalStatements']);
        $this->assertEquals(0.0, $profiles['totalElapsedSeconds']);
    }
    
    public function testProfilerWithProfiles(): void
    {
        $query0 = 'SELECT * FROM user';
        $query1 = 'SELECT count(*) FROM user';
        $query2 = 'SELECT * FROM user where id = :id';
        $params = ['id' => 1];
        $types = ['id' => Column::BIND_PARAM_INT];
        
        $connection = $this->getDb();
        assert($connection instanceof Mysql);
        
        $connection->query($query0);
        $connection->query($query1);
        $connection->query($query2, $params, $types);
        
        $profiles = $this->profiler->toArray();
        $this->assertIsArray($profiles['profiles']);
        $this->assertEquals(3, $profiles['numberTotalStatements']);
        $this->assertGreaterThan(0.0, $profiles['totalElapsedSeconds']);
        
        $this->assertEquals($query0, $profiles['profiles'][0]['sqlStatement']);
        $this->assertIsArray($profiles['profiles'][0]['sqlVariables']);
        $this->assertEmpty($profiles['profiles'][0]['sqlVariables']);
        $this->assertIsArray($profiles['profiles'][0]['sqlBindTypes']);
        $this->assertEmpty($profiles['profiles'][0]['sqlBindTypes']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][0]['initialTime']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][0]['finalTime']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][0]['elapsedSeconds']);
        
        $this->assertEquals($query1, $profiles['profiles'][1]['sqlStatement']);
        $this->assertIsArray($profiles['profiles'][1]['sqlVariables']);
        $this->assertEmpty($profiles['profiles'][1]['sqlVariables']);
        $this->assertIsArray($profiles['profiles'][1]['sqlBindTypes']);
        $this->assertEmpty($profiles['profiles'][1]['sqlBindTypes']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][1]['initialTime']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][1]['finalTime']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][1]['elapsedSeconds']);
        
        $this->assertEquals($query2, $profiles['profiles'][2]['sqlStatement']);
        $this->assertIsArray($profiles['profiles'][2]['sqlVariables']);
        $this->assertNotEmpty($profiles['profiles'][2]['sqlVariables']);
        $this->assertEquals($params, $profiles['profiles'][2]['sqlVariables']);
        $this->assertIsArray($profiles['profiles'][2]['sqlBindTypes']);
        $this->assertNotEmpty($profiles['profiles'][2]['sqlBindTypes']);
        $this->assertEquals($types, $profiles['profiles'][2]['sqlBindTypes']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][2]['initialTime']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][2]['finalTime']);
        $this->assertGreaterThan(0.0, $profiles['profiles'][2]['elapsedSeconds']);
    }
}
