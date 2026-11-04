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

namespace PhalconKit\Tests\Unit\Di\Fixtures;

use PhalconKit\Di\RequestScopedInterface;

/** Counts request-state resets for container reset tests. */
final class RequestScopedProbe implements RequestScopedInterface
{
    public int $resets = 0;

    public function resetRequestState(): void
    {
        $this->resets++;
    }
}
