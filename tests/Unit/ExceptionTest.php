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

use PhalconKit\Exception;
use PhalconKit\Exception\CliException;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Exception\ExceptionInterface;
use PhalconKit\Exception\HttpException;
use PhalconKit\Exception\InvalidArgumentException as PhalconKitInvalidArgumentException;
use PhalconKit\Exception\LogicException as PhalconKitLogicException;
use PhalconKit\Exception\RuntimeException as PhalconKitRuntimeException;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Exception\WsException;

class ExceptionTest extends AbstractUnit
{
    public function testBaseExceptionPreservesMessageCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('previous');
        $exception = new Exception('message', 123, $previous);

        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(123, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(ExceptionInterface::class, $exception);
    }

    public function testDomainExceptionsExtendBaseException(): void
    {
        $this->assertInstanceOf(Exception::class, new CliException());
        $this->assertInstanceOf(Exception::class, new HttpException());
        $this->assertInstanceOf(Exception::class, new WsException());
    }

    public function testFrameworkExceptionsPreserveNativePhpCategories(): void
    {
        $configurationException = new ConfigurationException('bad config');
        $invalidArgumentException = new PhalconKitInvalidArgumentException('bad argument');
        $logicException = new PhalconKitLogicException('bad logic');
        $runtimeException = new PhalconKitRuntimeException('bad runtime');
        $serviceException = new ServiceException('bad service');

        $this->assertInstanceOf(ExceptionInterface::class, $configurationException);
        $this->assertInstanceOf(\InvalidArgumentException::class, $configurationException);
        $this->assertSame('bad config', $configurationException->getMessage());

        $this->assertInstanceOf(ExceptionInterface::class, $invalidArgumentException);
        $this->assertInstanceOf(\InvalidArgumentException::class, $invalidArgumentException);
        $this->assertSame('bad argument', $invalidArgumentException->getMessage());

        $this->assertInstanceOf(ExceptionInterface::class, $logicException);
        $this->assertInstanceOf(\LogicException::class, $logicException);
        $this->assertSame('bad logic', $logicException->getMessage());

        $this->assertInstanceOf(ExceptionInterface::class, $runtimeException);
        $this->assertInstanceOf(\RuntimeException::class, $runtimeException);
        $this->assertSame('bad runtime', $runtimeException->getMessage());

        $this->assertInstanceOf(ExceptionInterface::class, $serviceException);
        $this->assertInstanceOf(\RuntimeException::class, $serviceException);
        $this->assertSame('bad service', $serviceException->getMessage());
    }
}
