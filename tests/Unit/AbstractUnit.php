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
    
    public function getDb(): Mysql
    {
        try {
            return $this->di->get('db');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database service is not available: ' . $e->getMessage());
            throw $e;
        }
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
