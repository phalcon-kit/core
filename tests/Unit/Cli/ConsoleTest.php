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

namespace PhalconKit\Tests\Unit\Cli;

use Phalcon\Di\FactoryDefault\Cli;
use PhalconKit\Bootstrap;
use PhalconKit\Tests\Unit\AbstractUnit;

class ConsoleTest extends AbstractUnit
{
    public \PhalconKit\Cli\Console $console;
    
    protected string $mode = Bootstrap::MODE_CLI;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->console = $this->di->get('console');
    }
    
    public function testConsoleFromDi(): void
    {
        $this->assertInstanceOf(\Phalcon\Cli\Console::class, $this->console);
        $this->assertInstanceOf(\PhalconKit\Cli\Console::class, $this->console);
    }

    public function testConstructorAcceptsContainer(): void
    {
        $di = new Cli();
        $console = new \PhalconKit\Cli\Console($di);

        $this->assertSame($di, $console->getDI());
    }
}
