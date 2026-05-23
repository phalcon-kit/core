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

namespace PhalconKit\Tests\Unit\Logger;

use Phalcon\Logger\Adapter\Noop;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Logger\Adapter\Syslog;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\Logger;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Logger\Loggers;
use PhalconKit\Tests\Unit\AbstractUnit;

class LoggersTest extends AbstractUnit
{
    private function createLoggers(array $options = []): Loggers
    {
        return new Loggers(array_replace_recursive([
            'formatters' => [
                'line' => Line::class,
            ],
            'drivers' => [
                'noop' => Noop::class,
                'stream' => Stream::class,
                'syslog' => Syslog::class,
            ],
            'default' => [
                'driver' => 'noop',
                'formatter' => 'line',
            ],
            'loggers' => [],
        ], $options));
    }

    public function testGetFormatterReturnsConfiguredFormatter(): void
    {
        $formatter = $this->createLoggers()->getFormatter('line', [
            'format' => '[%type%] %message%',
        ]);

        $this->assertInstanceOf(Line::class, $formatter);
        $this->assertSame('[%type%] %message%', $formatter->getFormat());
    }

    public function testGetFormatterAppliesDateFormatOption(): void
    {
        $formatter = $this->createLoggers()->getFormatter('line', [
            'dateFormat' => 'Y-m-d',
        ]);

        $this->assertInstanceOf(Line::class, $formatter);
        $this->assertSame('Y-m-d', $formatter->getDateFormat());
    }

    public function testGetFormatterRejectsUnknownFormatter(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Logger formatter `missing` is not defined.');

        $this->createLoggers()->getFormatter('missing');
    }

    public function testGetFormatterRejectsInvalidFormatterClass(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Logger formatter "line" must implement');

        $this->createLoggers([
            'formatters' => [
                'line' => \stdClass::class,
            ],
        ])->getFormatter('line');
    }

    public function testGetAdaptersReturnsConfiguredAdapters(): void
    {
        $adapters = $this->createLoggers()->getAdapters('noop');

        $this->assertArrayHasKey('noop', $adapters);
        $this->assertInstanceOf(Noop::class, $adapters['noop']);
    }

    public function testGetAdaptersAcceptsCommaSeparatedDrivers(): void
    {
        $adapters = $this->createLoggers()->getAdapters('noop,stream', [
            'path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR,
            'filename' => 'phalcon-kit-loggers-test.log',
        ]);

        $this->assertArrayHasKey('noop', $adapters);
        $this->assertArrayHasKey('stream', $adapters);
        $this->assertInstanceOf(Noop::class, $adapters['noop']);
        $this->assertInstanceOf(Stream::class, $adapters['stream']);
        $this->assertCount(2, $adapters);
    }

    public function testGetAdaptersSupportsSyslogDriver(): void
    {
        $adapters = $this->createLoggers()->getAdapters('syslog');

        $this->assertArrayHasKey('syslog', $adapters);
        $this->assertInstanceOf(Syslog::class, $adapters['syslog']);
    }

    public function testGetAdaptersRejectsUnknownDriver(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Logger driver adapter `missing` is not defined.');

        $this->createLoggers()->getAdapters('missing');
    }

    public function testGetAdaptersRejectsInvalidAdapterClass(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Logger driver adapter "noop" must implement');

        $this->createLoggers([
            'drivers' => [
                'noop' => \stdClass::class,
            ],
        ])->getAdapters('noop');
    }

    public function testLoadCreatesAndCachesLogger(): void
    {
        $loggers = $this->createLoggers([
            'loggers' => [
                'audit' => [
                    'driver' => 'noop',
                ],
            ],
        ]);

        $logger = $loggers->load('audit');

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($logger, $loggers->get('audit'));
        $this->assertSame($logger, $loggers->loggers['audit']);
    }

    public function testLoadPassesFormatterOptionsFromLoggerConfig(): void
    {
        $loggers = $this->createLoggers([
            'default' => [
                'driver' => 'noop',
                'formatter' => 'line',
                'format' => '[%date%] %message%',
                'dateFormat' => 'Y-m-d',
            ],
        ]);

        $logger = $loggers->load('audit');
        $adapter = $logger->getAdapters()['noop'];
        $formatter = $adapter->getFormatter();

        $this->assertInstanceOf(Line::class, $formatter);
        $this->assertSame('[%date%] %message%', $formatter->getFormat());
        $this->assertSame('Y-m-d', $formatter->getDateFormat());
    }

    public function testLoggerSpecificDateFormatOverridesDefaultFormatterOption(): void
    {
        $loggers = $this->createLoggers([
            'default' => [
                'driver' => 'noop',
                'formatter' => 'line',
                'dateFormat' => 'Y-m-d',
            ],
            'loggers' => [
                'audit' => [
                    'driver' => 'noop',
                    'formatter' => 'line',
                    'dateFormat' => 'Y',
                ],
            ],
        ]);

        $logger = $loggers->load('audit');
        $adapter = $logger->getAdapters()['noop'];
        $formatter = $adapter->getFormatter();

        $this->assertInstanceOf(Line::class, $formatter);
        $this->assertSame('Y', $formatter->getDateFormat());
    }

    public function testGetFallsBackToDefaultConfigForUnknownLoggerName(): void
    {
        $loggers = $this->createLoggers();

        $logger = $loggers->get('unknown');

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($logger, $loggers->get('unknown'));
    }

    public function testSetOverridesCachedLogger(): void
    {
        $loggers = $this->createLoggers();
        $logger = new Logger('manual', [
            'noop' => new Noop(),
        ]);

        $loggers->set('manual', $logger);

        $this->assertSame($logger, $loggers->get('manual'));
    }
}
