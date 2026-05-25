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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Actions;

use Phalcon\Http\Response;
use PhalconKit\Di\Di;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\AuthActionsControllerDouble;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\AuthActionsIdentityDouble;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\AuthActionsViewDouble;

class AuthActionsTest extends AbstractUnit
{
    public function testLoginActionKeepsJwtReturnedByLoginOverInitialJwt(): void
    {
        $view = new AuthActionsViewDouble();
        $identity = new AuthActionsIdentityDouble([
            'getJwt' => [
                'jwt' => 'anonymous-jwt',
                'refreshToken' => 'anonymous-refresh-token',
                'refreshed' => false,
            ],
            'login' => [
                'jwt' => 'authenticated-jwt',
                'refreshToken' => 'authenticated-refresh-token',
                'refreshed' => false,
                'loggedIn' => true,
                'loggedInAs' => false,
                'messages' => [],
            ],
            'getIdentity' => [
                'loggedIn' => true,
                'loggedInAs' => false,
                'user' => ['id' => 42],
            ],
        ]);
        $controller = $this->createController($view, $identity, [
            'email' => 'user@example.test',
            'password' => 'secret',
        ]);

        $this->assertTrue($controller->loginAction());
        $this->assertSame('authenticated-jwt', $view->getVar('jwt'));
        $this->assertSame('authenticated-refresh-token', $view->getVar('refreshToken'));
        $this->assertSame(['getJwt', 'login', 'getIdentity'], $identity->calls);
        $this->assertSame([
            'email' => 'user@example.test',
            'password' => 'secret',
        ], $identity->arguments['login'][0][0]);
    }

    public function testLoginAsActionKeepsJwtReturnedByLoginAsOverInitialJwt(): void
    {
        $view = new AuthActionsViewDouble();
        $identity = new AuthActionsIdentityDouble([
            'getJwt' => [
                'jwt' => 'current-jwt',
                'refreshToken' => 'current-refresh-token',
                'refreshed' => false,
            ],
            'loginAs' => [
                'jwt' => 'impersonated-jwt',
                'refreshToken' => 'impersonated-refresh-token',
                'refreshed' => false,
                'loggedIn' => true,
                'loggedInAs' => true,
                'messages' => [],
            ],
            'getIdentity' => [
                'loggedIn' => true,
                'loggedInAs' => true,
                'user' => ['id' => 99],
                'userAs' => ['id' => 42],
            ],
        ]);
        $controller = $this->createController($view, $identity, ['userId' => 99]);

        $this->assertTrue($controller->loginAsAction());
        $this->assertSame('impersonated-jwt', $view->getVar('jwt'));
        $this->assertSame('impersonated-refresh-token', $view->getVar('refreshToken'));
        $this->assertSame(['getJwt', 'loginAs', 'getIdentity'], $identity->calls);
        $this->assertSame(['userId' => 99], $identity->arguments['loginAs'][0][0]);
    }

    public function testLogoutActionPropagatesJwtReturnedByLogout(): void
    {
        $view = new AuthActionsViewDouble();
        $identity = new AuthActionsIdentityDouble([
            'logout' => [
                'jwt' => 'anonymous-jwt',
                'refreshToken' => 'anonymous-refresh-token',
                'refreshed' => false,
                'loggedIn' => false,
                'loggedInAs' => false,
            ],
        ]);
        $controller = $this->createController($view, $identity);

        $this->assertTrue($controller->logoutAction());
        $this->assertSame('anonymous-jwt', $view->getVar('jwt'));
        $this->assertSame('anonymous-refresh-token', $view->getVar('refreshToken'));
        $this->assertSame(['logout'], $identity->calls);
    }

    /**
     * Build a controller wired with only the services touched by AuthActions.
     *
     * The controller trait is intentionally tested at the response-var merge
     * boundary instead of through the full identity manager. Manager tests cover
     * credential and token behavior; these tests make sure action-level
     * `setVars()` ordering does not leak a stale pre-login JWT.
     *
     * @param array<string, mixed> $params Request params returned by the
     *     controller parameter helpers.
     */
    private function createController(
        AuthActionsViewDouble $view,
        AuthActionsIdentityDouble $identity,
        array $params = []
    ): AuthActionsControllerDouble {
        $di = new Di();
        $di->set('view', $view);
        $di->set('identity', $identity);
        $di->set('response', new Response());

        $controller = new AuthActionsControllerDouble();
        $controller->setParams($params);
        $controller->setDI($di);

        return $controller;
    }
}
