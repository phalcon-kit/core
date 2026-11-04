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

namespace PhalconKit\Tests\Unit\Di;

use Phalcon\Di\Di;
use PhalconKit\Di\RequestState;
use PhalconKit\Mvc\Model\Behavior\Blameable;
use PhalconKit\Mvc\Model\Behavior\Security;
use PhalconKit\Tests\Unit\Di\Fixtures\RequestScopedProbe;
use PHPUnit\Framework\TestCase;

final class RequestStateTest extends TestCase
{
    protected function tearDown(): void
    {
        Security::setRoles(null);
        Blameable::resetParentId();
    }

    public function testResetsBuiltSharedRequestScopedServicesOnly(): void
    {
        $di = new Di();

        $factoryCalls = 0;
        $di->setShared('resolved', function () use (&$factoryCalls): RequestScopedProbe {
            $factoryCalls++;
            return new RequestScopedProbe();
        });
        $resolved = $di->get('resolved');

        $instance = new RequestScopedProbe();
        $di->setShared('instance', $instance);

        $built = 0;
        $di->setShared('unbuilt', function () use (&$built): RequestScopedProbe {
            $built++;
            return new RequestScopedProbe();
        });

        $notShared = new RequestScopedProbe();
        $di->set('notShared', fn (): RequestScopedProbe => $notShared);
        $di->get('notShared');

        $di->setShared('plain', new \stdClass());

        RequestState::reset($di);

        $this->assertSame(1, $resolved->resets, 'The shared instance in use must be the one reset.');
        $this->assertSame(1, $factoryCalls, 'Reset must reuse the shared instance, not rebuild it.');
        $this->assertSame(1, $instance->resets);
        $this->assertSame(0, $built, 'Reset must not instantiate unused services.');
        $this->assertSame(0, $notShared->resets);
    }

    public function testResetsNonSharedServicesCachedByGetSharedOnce(): void
    {
        $di = new Di();
        $di->set('identity', fn (): RequestScopedProbe => new RequestScopedProbe());
        $cached = $di->getShared('identity');

        $shared = new RequestScopedProbe();
        $di->setShared('both', $shared);
        $di->getShared('both');

        RequestState::reset($di);

        $this->assertInstanceOf(RequestScopedProbe::class, $cached);
        $this->assertSame(1, $cached->resets, 'Injectable properties resolve through getShared().');
        $this->assertSame(1, $shared->resets, 'An instance reachable twice is reset once.');
    }

    public function testClearsModelSecurityRolesAndBlameableParentLink(): void
    {
        Security::setRoles(['admin']);
        $parentId = new \ReflectionProperty(Blameable::class, 'parentId');
        $parentId->setValue(null, 77);

        RequestState::reset(new Di());

        $roles = new \ReflectionProperty(Security::class, 'roles');
        $this->assertNull($roles->getValue());
        $this->assertNull($parentId->getValue());
    }
}
