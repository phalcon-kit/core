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

namespace PhalconKit\Dispatcher;

use Phalcon\Dispatcher\Exception as DispatcherException;

/**
 * Generic PhalconKit dispatcher helper.
 *
 * Native Phalcon 5.16 requires concrete dispatchers to implement the internal
 * exception hooks used by the dispatch loop. MVC and CLI dispatchers keep their
 * native specialized behavior; this generic wrapper provides a minimal
 * concrete implementation so shared helper behavior can still be tested and
 * used where no namespace-specific dispatcher is required.
 */
class AbstractDispatcher extends \Phalcon\Dispatcher\AbstractDispatcher implements DispatcherInterface
{
    use DispatcherTrait;

    /**
     * Bubble user exceptions unchanged for the generic dispatcher wrapper.
     *
     * Namespace-specific dispatchers can route exceptions through their
     * `dispatch:beforeException` event flow, but this base helper has no
     * controller/task domain to recover through.
     *
     * @param \Exception $exception Exception raised during dispatch.
     *
     * @throws \Exception Always rethrows the original exception.
     */
    #[\Override]
    protected function handleException(\Exception $exception): never
    {
        throw $exception;
    }

    /**
     * Raise a generic dispatcher exception for base-wrapper dispatch failures.
     *
     * @param string $message Diagnostic failure message.
     * @param int $exceptionCode Native dispatcher exception code.
     *
     * @throws DispatcherException Always throws the generated dispatcher
     *     exception.
     */
    #[\Override]
    protected function throwDispatchException(string $message, int $exceptionCode = 0): never
    {
        throw new DispatcherException($message, $exceptionCode);
    }
}
