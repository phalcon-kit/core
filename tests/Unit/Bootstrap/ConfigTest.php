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

namespace PhalconKit\Tests\Unit\Bootstrap;

use PhalconKit\Support\Env;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Bootstrap\Config;

class ConfigTest extends AbstractUnit
{
    public function testDefaultConfig(): void
    {
        $config = new Config();
        $keys = [
            'phalcon',
            'core',
            'app',
            'url',
            'php',
            'debug',
            'response',
            'identity',
            'models',
            'providers',
            'logger',
            'filters',
            'modules',
            'router',
            'view',
            'reCaptcha',
            'locale',
            'translate',
            'session',
            'module',
            'security',
            'cache',
            'metadata',
            'annotations',
            'database',
            'mailer',
            'cookies',
            'aws',
            'oauth2',
            'openai',
            'imap',
            'dotenv',
            'client',
            'permissions',
        ];
        
        // Default Configs should be defined
        foreach ($keys as $key) {
            $this->assertTrue($config->has($key));
        }
        
        // Every first level key should be grouped
        $keys = $config->keys();
        
        foreach ($keys as $key) {
            // Should be a Config object
            $this->assertInstanceOf(\Phalcon\Config\Config::class, $config->$key);
            $this->assertInstanceOf(\Phalcon\Config\Config::class, $config->get($key));
            $this->assertInstanceOf(\Phalcon\Config\Config::class, $config->path($key));
            
            // Should be able to extract array
            $this->assertIsArray($config->$key->toArray());
            $this->assertIsArray($config->get($key)->toArray());
            $this->assertIsArray($config->path($key)->toArray());
            $this->assertIsArray($config->pathToArray($key));
            $this->assertNull($config->pathToArray('non-existing-key'));
    
            // Should be clearable
            $config->get($key)->clear();
            $this->assertTrue($config->has($key));
            $this->assertEmpty($config->get($key)->toArray());
            $this->assertEquals(0, $config->get($key)->count());
            
            // Should be mutable
            $config->remove($key);
            $this->assertFalse($config->has($key));
            $this->assertNull($config->$key);
            $this->assertNull($config->get($key));
            $this->assertNull($config->path($key));
        }
        
        $this->assertNull($config->get('!@#$%^&*()'));
        $this->assertEquals(1, $config->get('!@#$%^&*()', 1));
    }
    
    public function testLoggerConfigUsesDateFormatKey(): void
    {
        $keys = [
            'LOGGER_DATE_FORMAT',
            'LOGGER_DATE',
            'LOGGER_ERROR_DATE_FORMAT',
            'LOGGER_ERROR_DATE',
        ];
        $previous = [];
        foreach ($keys as $key) {
            $previous[$key] = Env::get($key);
            Env::set($key, null);
        }

        try {
            Env::set('LOGGER_DATE_FORMAT', 'Y-m-d');
            Env::set('LOGGER_DATE', 'Y');
            Env::set('LOGGER_ERROR_DATE_FORMAT', 'c');
            Env::set('LOGGER_ERROR_DATE', 'U');

            $config = new Config();
            $loggerConfig = $config->pathToArray('logger.default');
            $errorLoggerConfig = $config->pathToArray('loggers.error');

            $this->assertArrayHasKey('dateFormat', $loggerConfig);
            $this->assertArrayNotHasKey('date', $loggerConfig);
            $this->assertSame('Y-m-d', $loggerConfig['dateFormat']);
            $this->assertSame('c', $errorLoggerConfig['dateFormat']);

            Env::set('LOGGER_ERROR_DATE_FORMAT', null);

            $fallbackConfig = new Config();

            $this->assertSame('Y-m-d', $fallbackConfig->path('loggers.error.dateFormat'));
        } finally {
            foreach ($previous as $key => $value) {
                Env::set($key, $value);
            }
        }
    }

    public function testIdentityStatelessConfigUsesEnvironmentFlag(): void
    {
        $previous = Env::get('IDENTITY_STATELESS');

        try {
            Env::set('IDENTITY_STATELESS', 'true');

            $config = new Config();

            $this->assertTrue($config->path('identity.stateless'));
        } finally {
            Env::set('IDENTITY_STATELESS', $previous);
        }
    }

    public function testGetDateTimeAppliesModifierFromProvidedDate(): void
    {
        $config = new Config();
        $date = new \DateTimeImmutable('2026-05-21 10:00:00');

        $this->assertSame(
            '2026-05-22 10:00:00',
            $config->getDateTime('+1 day', $date)->format('Y-m-d H:i:s')
        );
    }

    public function testPdoMysqlAttributeFallsBackToLegacyPdoConstant(): void
    {
        $method = new \ReflectionMethod(Config::class, 'pdoMysqlAttribute');

        $this->assertSame(
            \PDO::ATTR_ERRMODE,
            $method->invoke(null, 'MISSING_ATTRIBUTE', 'ATTR_ERRMODE')
        );
    }

    public function testGetModelClass(): void
    {
        $config = new \PhalconKit\Bootstrap\Config();
        $modelsMap = $config->get('models')->toArray();
        
        $models = $this->di->get('models');
        assert($models instanceof \PhalconKit\Support\Models);
        
        foreach ($modelsMap as $from => $to) {
            // Should be itself by default
            $this->assertEquals($to, $models->getClassMap($from));
            $this->assertEquals($to, $from);
            
            // Should be mutable
            $models->setClassMap($from, self::class);
            $this->assertEquals(self::class, $models->getClassMap($from));
    
            // Should be reset
            $models->removeClassMap($from);
            $this->assertEquals($from, $models->getClassMap($from));
        }
    
        // Should fall back to itself
        $this->assertEquals(self::class, $models->getClassMap(self::class));
    
        // Should be mutable
        $models->setClassMap(self::class, Config::class);
        $this->assertEquals(Config::class, $models->getClassMap(self::class));
    }
}
