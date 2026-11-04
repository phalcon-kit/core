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

use PhalconKit\Bootstrap;
use PhalconKit\Cli\ExceptionHandler;
use PhalconKit\Tests\Unit\AbstractUnit;

class ExceptionHandlerTest extends AbstractUnit
{
    protected function setUp(): void
    {
        /**
         * This setup method is intentionally left empty.
         * This test class does not require any specific initialization or fixtures.
         */
    }
    
    public function testExceptionHandler(): void
    {
        $testException = new \Exception('Test exception');
        
        // Create a memory stream to capture the output
        $memoryStream = fopen('php://memory', 'r+');
        
        // Instantiate your handler with the memory stream
        new ExceptionHandler($testException, $memoryStream)->write();
        
        // Rewind and read the content of the memory stream
        rewind($memoryStream);
        $output = stream_get_contents($memoryStream);
        
        // Assert the output contains your exception message
        $this->assertStringContainsString('Test exception', $output);
        
        // Clean up
        fclose($memoryStream);
    }

    public function testExceptionHandlerWritesStringMessages(): void
    {
        $memoryStream = fopen('php://memory', 'r+');

        new ExceptionHandler('Plain message', $memoryStream)->write();

        rewind($memoryStream);
        $this->assertSame('Plain message' . PHP_EOL, stream_get_contents($memoryStream));

        fclose($memoryStream);
    }

    public function testExceptionHandlerWritesOnlyTheMessageOfCliExceptions(): void
    {
        $memoryStream = fopen('php://memory', 'r+');

        new ExceptionHandler(new \PhalconKit\Exception\CliException('Error: 404 - Not Found'), $memoryStream)->write();

        rewind($memoryStream);
        $this->assertSame('Error: 404 - Not Found' . PHP_EOL, stream_get_contents($memoryStream));

        fclose($memoryStream);
    }

    public function testExceptionHandlerWritesThrowables(): void
    {
        $memoryStream = fopen('php://memory', 'r+');

        new ExceptionHandler(new \RuntimeException('Runtime message'), $memoryStream)->write();

        rewind($memoryStream);
        $output = (string) stream_get_contents($memoryStream);
        $this->assertStringContainsString('RuntimeException: Runtime message', $output);
        $this->assertStringContainsString('Stack trace:', $output);

        fclose($memoryStream);
    }
}
