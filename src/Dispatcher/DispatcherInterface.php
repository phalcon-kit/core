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

/**
 * Shared dispatcher contract for PhalconKit MVC, CLI, and WebSocket dispatchers.
 *
 * The interface keeps native Phalcon dispatcher behavior while adding two
 * framework conveniences: cycle-aware forward checks and diagnostic state
 * export. Dispatcher listeners can depend on this contract when they do not
 * care whether the active handler is an MVC controller or CLI/WebSocket task.
 *
 * @see https://docs.phalcon.io/latest/dispatcher/
 */
interface DispatcherInterface extends \Phalcon\Contracts\Dispatcher\Dispatcher
{
    /**
     * Determine whether a forward target differs from the effective dispatch.
     *
     * Resolve empty namespace/handler/action names to dispatcher defaults and
     * preserve null/omitted parts without changing the live dispatch state.
     * Module and parameter values remain part of the comparison. Controller
     * takes precedence over task, matching native forward() in either mode.
     * This is used by listeners that forward to error, maintenance, or
     * unauthorized routes and need to avoid forwarding back to themselves.
     *
     * @param array<string, mixed> $forward Forward route parts.
     */
    public function canForward(array $forward): bool;
    
    /**
     * Export dispatcher state for logs, diagnostics, and tests.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
