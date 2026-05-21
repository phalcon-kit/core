<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Logger;

use Phalcon\Logger\Adapter\AdapterInterface;
use Phalcon\Logger\Adapter\Noop;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Logger\Adapter\Syslog;
use Phalcon\Logger\Formatter\AbstractFormatter;
use Phalcon\Logger\Formatter\FormatterInterface;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\Logger;
use Phalcon\Logger\LoggerInterface;
use PhalconKit\Support\Options\Options;

class Loggers
{
    use Options;
    
    /**
     * An array to store logger objects
     * 
     * @var LoggerInterface[] $loggers
     */
    public array $loggers = [];
    
    /**
     * Gets a formatter based on the provided formatter name and options.
     *
     * @param string|null $formatter The name of the formatter to retrieve. Defaults to 'line'.
     * @param array $options The options for the formatter.
     * @return FormatterInterface The retrieved formatter.
     * @throws \Exception If the specified formatter is not defined.
     */
    public function getFormatter(?string $formatter = null, array $options = []): FormatterInterface
    {
        $formatter ??= 'line';
        $formatters = $this->getOption('formatters') ?? [];
        
        // Formatter must be defined
        if (!isset($formatters[$formatter])) {
            throw new \Exception('Logger formatter `' . $formatter . '` is not defined.');
        }
        
        // Formatter Instance
        $formatter = new $formatters[$formatter]();
        assert($formatter instanceof FormatterInterface);
        
        // Date Format
        if ($formatter instanceof AbstractFormatter) {
            if (isset($options['dateFormat'])) {
                $formatter->setDateFormat($options['dateFormat']);
            }
        }
        
        // Line Format
        if ($formatter instanceof Line) {
            if (isset($options['format'])) {
                $formatter->setFormat($options['format']);
            }
        }
        
        return $formatter;
    }
    
    /**
     * Returns an array of logger adapters based on the given drivers, formatter, and options.
     *
     * @param string|array|null $loggerDrivers The logger drivers to use. Defaults to null.
     * @param array $options The options to configure the adapters. Defaults to an empty array.
     * @param FormatterInterface|null $formatter The formatter to attach to the adapters. Defaults to null.
     * @return array The array of logger adapters.
     * @throws \Exception If a logger driver adapter is not defined.
     */
    public function getAdapters(string|array|null $loggerDrivers = null, array $options = [], FormatterInterface|null $formatter = null): array
    {
        $drivers = $this->getOption('drivers') ?? [];
        
        $formatter ??= $this->getFormatter();
        
        $ret = [];
        $loggerDrivers = is_array($loggerDrivers) ? $loggerDrivers : explode(',', $loggerDrivers ?? 'noop');
        foreach ($loggerDrivers as $loggerDriver) {
            if (!isset($drivers[$loggerDriver])) {
                throw new \Exception('Logger driver adapter `' . $loggerDriver . '` is not defined.');
            }
            
            $adapterClass = $drivers[$loggerDriver];
            
            // Stream
            if ($adapterClass === Stream::class) {
                $adapter = new Stream($options['path'] . $options['filename'], $options['options'] ?? []);
            }
            
            // Syslog
            if ($adapterClass === Syslog::class) {
                $adapter = new Syslog($loggerDriver, $options['options'] ?? []);
            }
            
            // Noop
            if ($adapterClass === Noop::class) {
                $adapter = new Noop();
            }
            
            // Others
            $adapter ??= new $drivers[$loggerDriver]($options['options'] ?? []);
            assert($adapter instanceof AdapterInterface);
            
            // Attach Formatter
            $adapter->setFormatter($formatter);
            
            // Add Adapter
            $ret [$loggerDriver] = $adapter;
        }
        
        return $ret;
    }
    
    /**
     * Loads a logger with the given name.
     *
     * @param string $name The name of the logger to load.
     * @return LoggerInterface The loaded logger.
     * @throws \Exception
     */
    public function load(string $name): LoggerInterface
    {
        $defaultConfig = $this->getOption('default') ?? [];
        $loggersConfig = $this->getOption('loggers') ?? [];
        $loggerConfig = $loggersConfig[$name] ?? [];

        $options = [
            'driver' => $loggerConfig['driver'] ?? $defaultConfig['driver'] ?? 'noop',
            'formatter' => $loggerConfig['formatter'] ?? $defaultConfig['formatter'] ?? 'line',
            'path' => $loggerConfig['path'] ?? $defaultConfig['path'] ?? null,
            'filename' => $loggerConfig['filename'] ?? $defaultConfig['filename'] ?? 'default.log',
            'dateFormat' => $loggerConfig['dateFormat'] ?? $defaultConfig['dateFormat'] ?? 'c',
            'format' => $loggerConfig['format'] ?? $defaultConfig['format'] ?? null,
            'options' => $loggerConfig['options'] ?? $defaultConfig['options'] ?? [],
        ];
        
        // get formatter
        $formatter = $this->getFormatter($options['formatter'], $options);
        
        // get adapters
        $adapters = $this->getAdapters($options['driver'], $options, $formatter);
        
        $logger = new Logger($name, $adapters);
        $this->set($name, $logger);
        return $logger;
    }
    
    /**
     * Retrieves a logger with the given name.
     *
     * @param string $name The name of the logger to retrieve.
     * @return LoggerInterface The retrieved logger.
     * @throws \Exception If the logger cannot be loaded.
     */
    public function get(string $name): LoggerInterface
    {
        if (isset($this->loggers[$name])) {
            return $this->loggers[$name];
        }
        
        return $this->load($name);
    }
    
    /**
     * Sets a logger with the given name.
     *
     * @param string $name The name of the logger to set.
     * @param LoggerInterface $logger The logger to set.
     * @return void
     */
    public function set(string $name, LoggerInterface $logger): void
    {
        $this->loggers[$name] = $logger;
    }
}
