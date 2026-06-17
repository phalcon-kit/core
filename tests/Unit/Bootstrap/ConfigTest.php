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
            'model',
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
            'acl',
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

    public function testOpenAiConfigExposesCanonicalKeysWithLegacyAliases(): void
    {
        $keys = [
            'OPENAI_API_KEY',
            'OPENAI_SECRET_KEY',
            'OPENAI_ORGANIZATION',
            'OPENAI_ORGANIZATION_ID',
            'OPENAI_PROJECT',
            'OPENAI_PROJECT_ID',
            'OPENAI_BASE_URI',
        ];
        $previous = [];
        foreach ($keys as $key) {
            $previous[$key] = Env::get($key);
            Env::set($key, null);
        }

        try {
            Env::set('OPENAI_API_KEY', 'canonical-key');
            Env::set('OPENAI_SECRET_KEY', 'legacy-secret');
            Env::set('OPENAI_ORGANIZATION', 'canonical-org');
            Env::set('OPENAI_ORGANIZATION_ID', 'legacy-org');
            Env::set('OPENAI_PROJECT', 'canonical-project');
            Env::set('OPENAI_PROJECT_ID', 'legacy-project');
            Env::set('OPENAI_BASE_URI', 'https://api.openai.test/v1');

            $config = new Config();
            $openAiConfig = $config->pathToArray('openai') ?? [];

            $this->assertSame('canonical-key', $openAiConfig['apiKey'] ?? null);
            $this->assertSame('legacy-secret', $openAiConfig['secretKey'] ?? null);
            $this->assertSame('canonical-org', $openAiConfig['organization'] ?? null);
            $this->assertSame('legacy-org', $openAiConfig['organizationId'] ?? null);
            $this->assertSame('canonical-project', $openAiConfig['project'] ?? null);
            $this->assertSame('legacy-project', $openAiConfig['projectId'] ?? null);
            $this->assertSame('https://api.openai.test/v1', $openAiConfig['baseUri'] ?? null);

            foreach ($keys as $key) {
                Env::set($key, null);
            }
            Env::set('OPENAI_SECRET_KEY', 'legacy-only-key');
            Env::set('OPENAI_ORGANIZATION_ID', 'legacy-only-org');
            Env::set('OPENAI_PROJECT_ID', 'legacy-only-project');

            $config = new Config();
            $openAiConfig = $config->pathToArray('openai') ?? [];

            $this->assertSame('legacy-only-key', $openAiConfig['apiKey'] ?? null);
            $this->assertSame('legacy-only-org', $openAiConfig['organization'] ?? null);
            $this->assertSame('legacy-only-project', $openAiConfig['project'] ?? null);
            $this->assertSame('api.openai.com/v1', $openAiConfig['baseUri'] ?? null);
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

    public function testModelRelationshipConfigUsesEnvironmentFlags(): void
    {
        $keys = [
            'MODEL_RELATIONSHIP_ENFORCE_DIRECT_OWNERSHIP',
            'MODEL_RELATIONSHIP_ALLOW_UNOWNED_DIRECT_RELATION_ADOPTION',
            'MODEL_RELATIONSHIP_AUTO_RESTORE_DIRECT_RELATIONS',
        ];
        $previous = [];
        foreach ($keys as $key) {
            $previous[$key] = Env::get($key);
            Env::set($key, null);
        }

        try {
            Env::set('MODEL_RELATIONSHIP_ENFORCE_DIRECT_OWNERSHIP', 'true');
            Env::set('MODEL_RELATIONSHIP_ALLOW_UNOWNED_DIRECT_RELATION_ADOPTION', 'false');
            Env::set('MODEL_RELATIONSHIP_AUTO_RESTORE_DIRECT_RELATIONS', 'true');

            $config = new Config();
            $relationship = $config->pathToArray('model.relationship') ?? [];

            $this->assertTrue($relationship['enforceDirectOwnership'] ?? null);
            $this->assertFalse($relationship['allowUnownedDirectRelationAdoption'] ?? null);
            $this->assertTrue($relationship['autoRestoreDirectRelations'] ?? null);
            $this->assertArrayNotHasKey('aliases', $relationship);
        } finally {
            foreach ($previous as $key => $value) {
                Env::set($key, $value);
            }
        }
    }

    public function testAclAttributesConfigUsesEnvironmentFlag(): void
    {
        $previous = Env::get('ACL_ATTRIBUTES');

        try {
            Env::set('ACL_ATTRIBUTES', 'false');

            $config = new Config();

            $this->assertFalse($config->path('acl.attributes'));

            Env::set('ACL_ATTRIBUTES', null);

            $config = new Config();

            $this->assertTrue($config->path('acl.attributes'));
        } finally {
            Env::set('ACL_ATTRIBUTES', $previous);
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

    public function testDefaultDatabaseInitCommandIncludesSessionSqlSettings(): void
    {
        $previous = Env::get('DATABASE_SQL_MODE');
        $method = new \ReflectionMethod(Config::class, 'pdoMysqlAttribute');
        $initCommandAttribute = $method->invoke(null, 'ATTR_INIT_COMMAND', 'MYSQL_ATTR_INIT_COMMAND');

        try {
            Env::set('DATABASE_SQL_MODE', null);

            $options = (new Config())->pathToArray('database.drivers.mysql.options') ?? [];

            $this->assertArrayHasKey($initCommandAttribute, $options);
            $this->assertIsString($options[$initCommandAttribute]);
            $this->assertStringStartsWith('SET NAMES ', $options[$initCommandAttribute]);
            $this->assertStringContainsString(', sql_mode = ', $options[$initCommandAttribute]);
            $this->assertStringContainsString('STRICT_TRANS_TABLES', $options[$initCommandAttribute]);
            $this->assertStringNotContainsString('ONLY_FULL_GROUP_BY', $options[$initCommandAttribute]);
            $this->assertStringContainsString(', block_encryption_mode = ', $options[$initCommandAttribute]);
            $this->assertArrayNotHasKey(0, $options);
        } finally {
            Env::set('DATABASE_SQL_MODE', $previous);
        }
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
