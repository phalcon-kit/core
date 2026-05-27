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

namespace PhalconKit\Tests\Unit;

use Phalcon\Autoload\Loader;
use Phalcon\Db\Adapter\Pdo\Mysql;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PhalconKit\Bootstrap;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Di\DiInterface;
use PhalconKit\Exception;
use PhalconKit\Support\Env;

/**
 * Class AbstractUnitTest
 * @package Tests\Unit
 */
#[CoversNothing]
abstract class AbstractUnit extends TestCase
{
    protected bool $loaded = false;
    
    protected ?Bootstrap $bootstrap = null;
    
    protected ?DiInterface $di = null;
    
    protected ?Loader $loader = null;
    
    protected string $mode = Bootstrap::MODE_MVC;
    
    /**
     * Return the configured database adapter or skip the test with context.
     *
     * Database-backed tests are valuable when MySQL is available, but local
     * development and some CI jobs intentionally run without it. Centralizing
     * the preflight keeps those skips distinguishable from assertion failures
     * and keeps each database test focused on the behavior it is protecting.
     */
    public function getDb(): Mysql
    {
        try {
            $database = $this->di?->get('db');
        } catch (\Throwable $e) {
            $this->skipUnavailableService('Database', $e);
            throw $e;
        }

        if (!$database instanceof Mysql) {
            $this->skipUnavailableService(
                'Database',
                detail: sprintf('expected "%s", got "%s".', Mysql::class, get_debug_type($database))
            );
        }

        return $database;
    }

    /**
     * Open a short-lived Redis probe connection or skip with a clear reason.
     *
     * Provider tests use this before asserting behavior that requires a real
     * Redis server. The caller owns the returned connection and should close it
     * once the preflight has succeeded.
     */
    protected function getLocalRedisProbe(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 0.01
    ): \Redis {
        $this->requireExtensionForOptionalService(\Redis::class, 'Redis');

        $probe = new \Redis();
        try {
            $connected = $probe->connect($host, $port, $timeout);
        }
        catch (\RedisException $exception) {
            $this->skipUnavailableService('Redis', $exception);
        }

        if (!$connected) {
            $this->skipUnavailableService('Redis', detail: 'connection attempt returned false.');
        }

        return $probe;
    }

    /**
     * Skip an optional-service test when the required PHP extension is missing.
     */
    protected function requireExtensionForOptionalService(string $className, string $extensionName): void
    {
        if (!class_exists($className)) {
            $this->skipUnavailableExtension($extensionName, sprintf('PHP class "%s" is not available.', $className));
        }
    }

    /**
     * Mark an optional PHP extension as unavailable with consistent text.
     */
    protected function skipUnavailableExtension(string $extensionName, ?string $detail = null): never
    {
        $message = sprintf('%s extension is not available.', $extensionName);
        $detail = trim($detail ?? '');

        if ($detail !== '') {
            $message = sprintf('%s extension is not available: %s', $extensionName, $detail);
        }

        $this->markTestSkipped($message);
    }

    /**
     * Mark an optional service as unavailable with consistent CI-facing text.
     *
     * @param string $serviceName Human-readable service name, for example
     *     `Database` or `Redis`.
     * @param \Throwable|null $exception Optional exception raised by the
     *     service preflight.
     * @param string|null $detail Optional explicit detail when no exception was
     *     thrown.
     */
    protected function skipUnavailableService(
        string $serviceName,
        ?\Throwable $exception = null,
        ?string $detail = null
    ): never {
        $message = sprintf('%s service is not available.', $serviceName);
        $reason = trim($detail ?? $exception?->getMessage() ?? '');

        if ($reason !== '') {
            $message = sprintf('%s service is not available: %s', $serviceName, $reason);
        }

        $this->markTestSkipped($message);
    }
    
    public function getConfig(): Config
    {
        return $this->di->get('config');
    }
    
    /**
     * Phalcon Kit Setup
     * @throws Exception
     */
    protected function setUp(): void
    {
        $rootDir = dirname(dirname(__DIR__)) . '/';
        Env::setNames(['.env.testing', '.env.testing.local']);
        Env::load(null, null, false);
        
        $loader = new Loader();
        $loader->setFiles([$rootDir . '/vendor/autoload.php']);
        $loader->setNamespaces(['PhalconKit' => $rootDir . '/src']);
        $loader->setFileCheckingCallback(null);
        $loader->register();
        
        $this->bootstrap = new Bootstrap($this->mode);
        $this->di = $this->bootstrap->di;
        $this->loader = $loader;
        $this->loaded = true;
        parent::setUp();
    }
    
    protected function tearDown(): void
    {
        $this->loader = null;
        $this->bootstrap = null;
        $this->di = null;
        $this->loaded = false;
        $this->restoreExceptionHandler();
        parent::tearDown();
    }
    
    public function restoreExceptionHandler(): void
    {
        restore_exception_handler();
    }
    
    public function setErrorHandler(int $errorLevels = E_ALL): void
    {
        set_error_handler(
            static function (int $code, string $message) {
                restore_error_handler();
                throw new \Exception($message, $code);
            },
            $errorLevels
        );
    }
}
