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
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Support\Options\Options;

/**
 * Factory and registry for named Phalcon logger instances.
 *
 * The service is configured from the `logger` and `loggers` config sections.
 * It lazily builds named loggers, caches them for repeated calls, and applies
 * formatter/adapter options consistently across default and logger-specific
 * configuration.
 *
 * @see https://docs.phalcon.io/5.16/logger/
 */
class Loggers
{
    use Options;
    
    /**
     * Cached logger instances keyed by logger name.
     * 
     * @var array<string, LoggerInterface>
     */
    public array $loggers = [];
    
    /**
     * Create a configured formatter by name.
     *
     * The formatter name is resolved from the configured `formatters` map. Line
     * formatters receive the optional `format` value and all
     * AbstractFormatter instances receive the optional `dateFormat` value.
     *
     * @param string|null $formatter The name of the formatter to retrieve. Defaults to 'line'.
     * @param array<string, mixed> $options Formatter options from the selected
     *        logger config.
     * @return FormatterInterface The retrieved formatter.
     * @throws ConfigurationException If the formatter name is not configured or
     *         the configured formatter class does not implement
     *         FormatterInterface.
     */
    public function getFormatter(?string $formatter = null, array $options = []): FormatterInterface
    {
        $formatter ??= 'line';
        $formatters = $this->getOption('formatters') ?? [];
        
        // Formatter must be defined
        if (!isset($formatters[$formatter])) {
            throw new ConfigurationException('Logger formatter `' . $formatter . '` is not defined.');
        }
        
        // Formatter Instance
        $formatter = $this->createFormatter($formatter, $formatters[$formatter]);
        
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
     * Create configured logger adapters for one or more driver names.
     *
     * Driver names are resolved from the configured `drivers` map. The method
     * accepts either an array of names or a comma-separated string such as
     * `noop,stream`. Every adapter receives the provided formatter before it is
     * returned.
     *
     * @param string|array|null $loggerDrivers The logger drivers to use. Defaults to null.
     * @param array<string, mixed> $options Adapter options from the selected
     *        logger config. Stream adapters expect `path` and `filename`;
     *        custom adapters receive `options`.
     * @param FormatterInterface|null $formatter The formatter to attach to the adapters. Defaults to null.
     * @return array<string, AdapterInterface> The array of logger adapters by
     *         driver name.
     * @throws ConfigurationException If a driver name is not configured or the
     *         configured adapter class does not implement AdapterInterface.
     */
    public function getAdapters(string|array|null $loggerDrivers = null, array $options = [], FormatterInterface|null $formatter = null): array
    {
        $drivers = $this->getOption('drivers') ?? [];
        
        $formatter ??= $this->getFormatter();
        
        $ret = [];
        $loggerDrivers = is_array($loggerDrivers) ? $loggerDrivers : explode(',', $loggerDrivers ?? 'noop');
        foreach ($loggerDrivers as $loggerDriver) {
            if (!isset($drivers[$loggerDriver])) {
                throw new ConfigurationException('Logger driver adapter `' . $loggerDriver . '` is not defined.');
            }
            
            $adapter = $this->createAdapter($loggerDriver, $drivers[$loggerDriver], $options);
            
            // Attach Formatter
            $adapter->setFormatter($formatter);
            
            // Add Adapter
            $ret [$loggerDriver] = $adapter;
        }
        
        return $ret;
    }
    
    /**
     * Build, cache, and return a named logger.
     *
     * Logger-specific options in the `loggers.<name>` config section override
     * the `logger.default` values. Missing named logger config falls back to the
     * default logger options, which makes ad-hoc logger names possible while
     * preserving a consistent adapter/formatter setup.
     *
     * @param string $name The name of the logger to load.
     * @return LoggerInterface The loaded logger.
     * @throws ConfigurationException If formatter or adapter configuration is
     *         invalid.
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
     * Retrieve a cached logger or lazily load it from configuration.
     *
     * @param string $name The name of the logger to retrieve.
     * @return LoggerInterface The retrieved logger.
     * @throws ConfigurationException If formatter or adapter configuration is
     *         invalid while loading the logger.
     */
    public function get(string $name): LoggerInterface
    {
        if (isset($this->loggers[$name])) {
            return $this->loggers[$name];
        }
        
        return $this->load($name);
    }
    
    /**
     * Store or replace a named logger instance.
     *
     * @param string $name The name of the logger to set.
     * @param LoggerInterface $logger The logger to set.
     */
    public function set(string $name, LoggerInterface $logger): void
    {
        $this->loggers[$name] = $logger;
    }

    /**
     * Instantiate a configured formatter class after validating its contract.
     *
     * Formatter names come from configuration, so this private helper guards the
     * dynamic class name before instantiation and returns a framework-scoped
     * exception when configuration is invalid.
     *
     * @param string $formatter Logical formatter name from config.
     * @param string $formatterClass Configured formatter class name.
     *
     * @return FormatterInterface Formatter instance.
     *
     * @throws ConfigurationException When the configured class does not
     *     implement Phalcon's formatter contract.
     */
    private function createFormatter(string $formatter, string $formatterClass): FormatterInterface
    {
        if (!is_a($formatterClass, FormatterInterface::class, true)) {
            throw new ConfigurationException(sprintf(
                'Logger formatter "%s" must implement "%s"; got "%s".',
                $formatter,
                FormatterInterface::class,
                $formatterClass
            ));
        }

        /**
         * @var class-string<FormatterInterface> $formatterClass
         * @psalm-suppress UnsafeInstantiation Logger formatters are configured as zero-argument services.
         */
        return new $formatterClass();
    }

    /**
     * Instantiate a configured adapter class for a logger driver.
     *
     * Built-in Phalcon adapters have different constructor signatures, so this
     * method centralizes those differences. Custom adapters are expected to
     * accept the configured `options` array.
     *
     * @param string $loggerDriver Logical driver name from config.
     * @param string $adapterClass Configured adapter class name.
     * @param array<string, mixed> $options Resolved logger options.
     *
     * @return AdapterInterface Adapter instance ready to receive a formatter.
     *
     * @throws ConfigurationException When the configured class does not
     *     implement Phalcon's adapter contract.
     */
    private function createAdapter(string $loggerDriver, string $adapterClass, array $options): AdapterInterface
    {
        if (!is_a($adapterClass, AdapterInterface::class, true)) {
            throw new ConfigurationException(sprintf(
                'Logger driver adapter "%s" must implement "%s"; got "%s".',
                $loggerDriver,
                AdapterInterface::class,
                $adapterClass
            ));
        }

        return match ($adapterClass) {
            Stream::class => new Stream($options['path'] . $options['filename'], $options['options'] ?? []),
            Syslog::class => new Syslog($loggerDriver, $options['options'] ?? []),
            Noop::class => new Noop(),
            default => $this->createCustomAdapter($adapterClass, $options),
        };
    }

    /**
     * Instantiate a custom logger adapter with its configured options array.
     *
     * This keeps built-in adapter branching small while still allowing
     * applications to register their own Phalcon-compatible adapter classes in
     * config.
     *
     * @param string $adapterClass Adapter class already validated against
     *     AdapterInterface.
     * @param array<string, mixed> $options Resolved logger options.
     *
     * @return AdapterInterface Custom adapter instance.
     */
    private function createCustomAdapter(string $adapterClass, array $options): AdapterInterface
    {
        /**
         * @var class-string<AdapterInterface> $adapterClass
         * @psalm-suppress UnsafeInstantiation Custom logger adapters are configured to accept an options array.
         */
        return new $adapterClass($options['options'] ?? []);
    }
}
