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

final class AuthActionsIdentityDouble
{
    /**
     * @var list<string>
     */
    public array $calls = [];

    /**
     * @var array<string, list<array<int, mixed>>>
     */
    public array $arguments = [];

    /**
     * @param array<string, array<string, mixed>> $responses Method responses
     *     keyed by identity method name.
     */
    public function __construct(private array $responses)
    {
    }

    /**
     * Return the configured JWT payload.
     *
     * @return array<string, mixed>
     */
    public function getJwt(bool $refresh = false): array
    {
        return $this->respond('getJwt', [$refresh]);
    }

    /**
     * Return the configured login response.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function login(array $params = []): array
    {
        return $this->respond('login', [$params]);
    }

    /**
     * Return the configured impersonation response.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function loginAs(array $params = []): array
    {
        return $this->respond('loginAs', [$params]);
    }

    /**
     * Return the configured logout response.
     *
     * @return array<string, mixed>
     */
    public function logout(): array
    {
        return $this->respond('logout');
    }

    /**
     * Return the configured identity payload.
     *
     * @param array<string, mixed>|null $userExpose
     *
     * @return array<string, mixed>
     */
    public function getIdentity(?array $userExpose = null): array
    {
        return $this->respond('getIdentity', [$userExpose]);
    }

    /**
     * Record a method call and return its configured response.
     *
     * @param list<mixed> $arguments
     *
     * @return array<string, mixed>
     */
    private function respond(string $method, array $arguments = []): array
    {
        $this->calls[] = $method;
        $this->arguments[$method][] = $arguments;

        return $this->responses[$method] ?? [];
    }
}
