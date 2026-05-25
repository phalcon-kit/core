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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use PhalconKit\Mvc\Controller;
use PhalconKit\Mvc\Controller\Traits\Actions\AuthActions;

final class AuthActionsControllerDouble extends Controller
{
    use AuthActions;

    /**
     * @var array<string, mixed>
     */
    private array $params = [];

    /**
     * Set the request parameter payload returned by the test helper methods.
     *
     * Phalcon controllers have a final constructor, so tests configure the
     * action double after instantiation to mirror the framework lifecycle.
     *
     * @param array<string, mixed> $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Return one configured request parameter.
     *
     * The action trait only needs this for reset-token paths, but the method is
     * part of the parameter helper contract and keeps the double usable for
     * future action tests.
     *
     * @param array<int, string>|string|null $filters Ignored by the test
     *     double; production filtering is covered by controller parameter
     *     tests.
     * @param array<string, mixed>|null $params Optional override payload.
     */
    public function getParam(
        string $key,
        array|string|null $filters = null,
        mixed $default = null,
        ?array $params = null
    ): mixed {
        $source = $params ?? $this->params;
        return $source[$key] ?? $default;
    }

    /**
     * Return the configured request parameter payload.
     *
     * @param array<string, mixed>|null $fields Ignored by the double because
     *     each test already provides the exact action payload it wants to
     *     observe.
     *
     * @return array<string, mixed>
     */
    public function getParams(?array $fields = null, bool $cached = true, bool $deep = true): array
    {
        return $this->params;
    }
}
