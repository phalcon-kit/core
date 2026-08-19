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

use Phalcon\Contracts\Events\Event as EventContract;
use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Logger\AbstractLogger;
use Phalcon\Logger\LoggerInterface;
use PhalconKit\Db\Events\Logger as DatabaseEventLogger;
use PhalconKit\Tests\Unit\AbstractUnit;

class LoggerTest extends AbstractUnit
{
    public LoggerInterface $logger;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->di->get('logger');
    }
    
    public function testLoggerFromDi(): void
    {
        $this->assertInstanceOf(AbstractLogger::class, $this->logger);
        $this->assertInstanceOf(LoggerInterface::class, $this->logger);
    }
    
    public function testQueryEventLogger(): void
    {
        // set database logger to stream
        $loggerConfig = $this->getConfig()->pathToArray('loggers.database');
        $filePath = $loggerConfig['path'] . $loggerConfig['filename'];
        $query = 'SELECT 1';
        
        // disable logger completely
        $this->getConfig()->set('logger.enable', false);
        $this->getConfig()->set('loggers.database.enable', false);
        
        // remove existing logs
        $this->assertTrue(!file_exists($filePath) || unlink($filePath));
        $this->assertFalse(file_exists($filePath));
        
        // make a query
        $this->getDb()->query($query);
        
        // file should not exist
        $this->assertFalse(file_exists($filePath));
        
        // enable database logger
        $this->getConfig()->set('logger.enable', true);
        $this->getConfig()->set('loggers.database.enable', true);
        
        // make a query
        $this->getDb()->query($query);
        
        // check if file exists
        $this->assertTrue(file_exists($filePath));
        
        // add this to check if log file contains the query
        $logContent = file_get_contents($filePath);
        $this->assertTrue(str_contains($logContent, $query));
        
        // remove logs
        $this->assertTrue(unlink($filePath));
        $this->assertFalse(file_exists($filePath));
    }

    public function testConnectionLostEventWritesPrivacySafeWarning(): void
    {
        $loggerConfig = $this->getConfig()->pathToArray('loggers.database');
        $filePath = $loggerConfig['path'] . $loggerConfig['filename'];
        $this->getConfig()->set('logger.enable', true);
        $this->getConfig()->set('loggers.database.enable', true);
        $this->assertTrue(!file_exists($filePath) || unlink($filePath));

        $event = $this->createStub(EventContract::class);
        $event->method('getType')->willReturn('connectionLost');
        $event->method('getData')->willReturn(['sql' => 'SELECT sensitive_data']);
        $connection = $this->createStub(AbstractAdapter::class);
        $connection->method('getConnectionId')->willReturn(17);
        $listener = new DatabaseEventLogger();
        $listener->setDI($this->di);

        $listener->connectionLost($event, $connection);

        $logContent = file_get_contents($filePath);
        $this->assertIsString($logContent);
        $this->assertStringContainsString('connectionLost', $logContent);
        $this->assertStringContainsString('"connectionId":17', $logContent);
        $this->assertStringNotContainsString('sensitive_data', $logContent);
        $this->assertStringNotContainsString('sqlStatement', $logContent);
        $this->assertTrue(unlink($filePath));
    }
}
