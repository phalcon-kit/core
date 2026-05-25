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

namespace PhalconKit\Tests\Unit\Identity;

use Phalcon\Di\Di;
use Phalcon\Messages\Message;
use Phalcon\Messages\Messages;
use Phalcon\Db\Column;
use PhalconKit\Config\Config;
use PhalconKit\Identity\Manager;
use PhalconKit\Identity\ManagerInterface;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Tests\Unit\Identity\Fixtures\IdentityPropertyListUserDouble;
use PhalconKit\Tests\Unit\Identity\Fixtures\IdentityUserModelDouble;
use PhalconKit\Tests\Unit\AbstractUnit;

class ManagerTest extends AbstractUnit
{
    public function testInstanceOf(): void
    {
        $identity = new Manager();

        $this->assertInstanceOf(Manager::class, $identity);
        $this->assertInstanceOf(ManagerInterface::class, $identity);
    }

    public function testSessionKeyUsesDefaultCustomAndRefreshSuffix(): void
    {
        $identity = new Manager();

        $this->assertSame(Manager::SESSION_KEY, $identity->getSessionKey());
        $this->assertSame(Manager::SESSION_KEY . Manager::REFRESH_SUFFIX, $identity->getSessionKey(true));

        $identity = new Manager(['sessionKey' => 'unit-identity']);

        $this->assertSame('unit-identity', $identity->getSessionKey());
        $this->assertSame('unit-identity-refresh', $identity->getSessionKey(true));
    }

    public function testSessionIdentityCanBeStoredReadCheckedAndRemoved(): void
    {
        $session = $this->createSession();
        $identity = $this->createManager(session: $session);
        $identity->setClaim(['key' => 'unit-session-key']);

        $this->assertFalse($identity->hasSessionIdentity());
        $this->assertSame([], $identity->getSessionIdentity());

        $identity->setSessionIdentity(['userId' => 42, 'asUserId' => 7]);

        $this->assertTrue($identity->hasSessionIdentity());
        $this->assertSame(['userId' => 42, 'asUserId' => 7], $identity->getSessionIdentity());
        $this->assertSame(['userId' => 42, 'asUserId' => 7], $session->data['unit-session-key']);

        $identity->removeSessionIdentity();

        $this->assertFalse($identity->hasSessionIdentity());
        $this->assertSame([], $identity->getSessionIdentity());
        $this->assertArrayNotHasKey('unit-session-key', $session->data);
    }

    public function testStatelessIdentityStoresPayloadInClaimInsteadOfSession(): void
    {
        $session = $this->createSession();
        $identity = $this->createManager(['identity' => ['stateless' => true]], session: $session);
        $identity->setClaim(['key' => 'unit-session-key']);

        $this->assertFalse($identity->hasSessionIdentity());
        $this->assertSame([], $identity->getSessionIdentity());

        $identity->setSessionIdentity(['userId' => 42, 'asUserId' => 7]);

        $this->assertTrue($identity->hasSessionIdentity());
        $this->assertSame(['userId' => 42, 'asUserId' => 7], $identity->getSessionIdentity());
        $this->assertSame(['key' => 'unit-session-key', 'userId' => 42, 'asUserId' => 7], $identity->claim);
        $this->assertSame([], $session->data);

        $identity->removeSessionIdentity();

        $this->assertFalse($identity->hasSessionIdentity());
        $this->assertSame([], $identity->getSessionIdentity());
        $this->assertSame(['key' => 'unit-session-key'], $identity->claim);
        $this->assertSame([], $session->data);
    }

    public function testLogoutRemovesSessionIdentityAndReportsLoggedOutState(): void
    {
        $identity = $this->createManager();
        $identity->setClaim(['key' => 'unit-session-key']);
        $identity->setSessionIdentity(['userId' => 42, 'asUserId' => 7]);

        $result = $identity->logout();

        $this->assertSame([
            'loggedIn' => false,
            'loggedInAs' => false,
        ], $result);
        $this->assertFalse($identity->hasSessionIdentity());
    }

    public function testStatelessLogoutClearsClaimIdentityAndReturnsAnonymousTokens(): void
    {
        $session = $this->createSession();
        $jwt = $this->createJwtService();
        $identity = $this->createManager(
            ['identity' => ['stateless' => true]],
            session: $session,
            request: $this->createRequest(),
            jwt: $jwt
        );
        $identity->setClaim(['key' => 'unit-session-key', 'userId' => 42]);

        $result = $identity->logout();

        $this->assertSame('token:' . Manager::SESSION_KEY, $result['jwt']);
        $this->assertSame('token:' . Manager::SESSION_KEY . Manager::REFRESH_SUFFIX, $result['refreshToken']);
        $this->assertFalse($result['refreshed']);
        $this->assertFalse($result['loggedIn']);
        $this->assertFalse($result['loggedInAs']);
        $this->assertSame(['key' => 'unit-session-key'], $identity->claim);
        $this->assertSame('{"key":"unit-session-key"}', $jwt->builderOptions[0]['subject']);
        $this->assertSame([], $session->data);
    }

    public function testGetDelegatesToIdentityPayload(): void
    {
        $identity = new class extends Manager {
            public function getIdentity(?array $userExpose = null): array
            {
                return ['expose' => $userExpose];
            }
        };

        $this->assertSame(['expose' => ['id']], $identity->get(['id']));
    }

    public function testGetIdentityBuildsPayloadWithExposedUsersAndKeyedLists(): void
    {
        $admin = $this->createKeyedEntity('admin');
        $developer = $this->createKeyedEntity('developer');
        $member = $this->createKeyedEntity('member');
        $internal = $this->createKeyedEntity('internal');
        $impersonator = $this->createUserDouble(['id' => 7], []);
        $user = $this->createUserDouble(['id' => 42], [
            'rolelist' => [$admin, $developer],
            'grouplist' => [$member],
            'typelist' => [$internal],
        ]);

        $identity = new class extends Manager {
            public ?UserInterface $unitUser = null;

            public ?UserInterface $unitUserAs = null;

            public function getUser(bool $as = false, ?bool $force = null): ?UserInterface
            {
                return $as ? $this->unitUserAs : $this->unitUser;
            }
        };
        $identity->unitUser = $user;
        $identity->unitUserAs = $impersonator;

        $payload = $identity->getIdentity(['id']);

        $this->assertSame([
            'loggedInAs' => true,
            'userAs' => ['id' => 7],
            'loggedIn' => true,
            'user' => ['id' => 42],
            'roleList' => [
                'admin' => $admin,
                'developer' => $developer,
            ],
            'typeList' => [
                'internal' => $internal,
            ],
            'groupList' => [
                'member' => $member,
            ],
        ], $payload);
    }

    public function testGetIdentityCollectsDirtyPropertyAndMissingLists(): void
    {
        $role = $this->createKeyedEntity('editor');
        $type = $this->createKeyedEntity('staff');
        $propertyListUser = new IdentityPropertyListUserDouble();
        $propertyListUser->typelist = [$type];

        $identity = new class extends Manager {
            public ?UserInterface $unitUser = null;

            public function getUser(bool $as = false, ?bool $force = null): ?UserInterface
            {
                return $as ? null : $this->unitUser;
            }
        };
        $identity->unitUser = $this->createUserDouble(null, [], ['rolelist' => [$role]]);

        $dirtyPayload = $identity->getIdentity();

        $this->assertSame(['editor' => $role], $dirtyPayload['roleList']);
        $this->assertSame([], $dirtyPayload['typeList']);
        $this->assertSame([], $dirtyPayload['groupList']);

        $identity->unitUser = $propertyListUser;
        $propertyPayload = $identity->getIdentity();

        $this->assertSame([], $propertyPayload['roleList']);
        $this->assertSame(['staff' => $type], $propertyPayload['typeList']);
        $this->assertSame([], $propertyPayload['groupList']);
    }

    public function testGetIdentityReturnsEmptyListsWithoutUser(): void
    {
        $identity = new class extends Manager {
            public function getUser(bool $as = false, ?bool $force = null): ?UserInterface
            {
                return null;
            }
        };

        $payload = $identity->getIdentity();

        $this->assertFalse($payload['loggedIn']);
        $this->assertFalse($payload['loggedInAs']);
        $this->assertNull($payload['user']);
        $this->assertNull($payload['userAs']);
        $this->assertSame([], $payload['roleList']);
        $this->assertSame([], $payload['typeList']);
        $this->assertSame([], $payload['groupList']);
    }

    public function testGetIdentityRejectsRelatedEntitiesWithoutKeyMethod(): void
    {
        $identity = new class extends Manager {
            public ?UserInterface $unitUser = null;

            public function getUser(bool $as = false, ?bool $force = null): ?UserInterface
            {
                return $as ? null : $this->unitUser;
            }
        };
        $identity->unitUser = $this->createUserDouble(null, ['rolelist' => [new \stdClass()]]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must implement method getKey()');

        $identity->getIdentity();
    }

    public function testHasUsesDefaultAnyMatchAndForcedAllMatchSemantics(): void
    {
        $identity = new Manager();

        $this->assertFalse($identity->has(null, ['admin']));
        $this->assertFalse($identity->has([], ['admin']));
        $this->assertTrue($identity->has('admin', ['admin']));
        $this->assertTrue($identity->has(['admin', 'developer'], ['admin']));
        $this->assertFalse($identity->has(['admin', 'developer'], ['admin'], true));
        $this->assertTrue($identity->has(['admin', 'developer'], ['admin', 'developer'], true));
        $this->assertTrue($identity->has([['developer', 'admin']], ['admin'], true));
    }

    public function testRoleInheritanceResolvesRecursivelyAndIncludesEveryone(): void
    {
        $identity = $this->createManager([
            'permissions' => [
                'roles' => [
                    'admin' => [
                        'inherit' => ['manager'],
                    ],
                    'manager' => [
                        'inherit' => ['user'],
                    ],
                ],
            ],
        ]);

        $this->assertSame(['manager', 'user', 'everyone'], $identity->getInheritedRoleList(['admin']));
        $this->assertSame(['guest', 'everyone'], $identity->getInheritedRoleList());
    }

    public function testAclRolesIncludeContextEveryoneBaseAndInheritedRoles(): void
    {
        $identity = $this->createRoleAwareManager(['admin' => true], [
            'permissions' => [
                'roles' => [
                    'admin' => [
                        'inherit' => ['manager'],
                    ],
                ],
            ],
        ], ws: true, cli: true);

        $roles = $identity->getAclRoles();

        foreach (['ws', 'cli', 'everyone', 'admin', 'manager'] as $role) {
            $this->assertArrayHasKey($role, $roles);
            $this->assertSame($role, $roles[$role]->getName());
        }

        $guestIdentity = $this->createRoleAwareManager([]);
        $guestRoles = $guestIdentity->getAclRoles();

        $this->assertArrayHasKey('everyone', $guestRoles);
        $this->assertArrayHasKey('guest', $guestRoles);
    }

    public function testHasAclRoleChecksEffectiveAclRoles(): void
    {
        $identity = $this->createRoleAwareManager(['admin' => true], [
            'permissions' => [
                'roles' => [
                    'admin' => [
                        'inherit' => ['manager'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($identity->hasAclRole(['manager']));
        $this->assertTrue($identity->hasAclRole(['admin', 'missing']));
        $this->assertFalse($identity->hasAclRole(['admin', 'missing'], true));
    }

    public function testHasRoleCanIncludeOrIgnoreInheritedRoles(): void
    {
        $identity = $this->createRoleAwareManager(['admin' => true], [
            'permissions' => [
                'roles' => [
                    'admin' => [
                        'inherit' => ['manager'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($identity->hasRole(['manager']));
        $this->assertFalse($identity->hasRole(['manager'], inherit: false));
    }

    public function testLoginReturnsValidationMessagesForMissingCredentials(): void
    {
        $identity = $this->createLoginManager();
        $identity->setClaim(['key' => 'unit-session-key']);

        $result = $identity->login();

        $this->assertFalse($result['loggedIn']);
        $this->assertFalse($result['loggedInAs']);
        $this->assertInstanceOf(Messages::class, $result['messages']);
        $this->assertGreaterThan(0, $result['messages']->count());
    }

    public function testLoginFailsWithoutMatchingUser(): void
    {
        $identity = $this->createLoginManager();
        $identity->setClaim(['key' => 'unit-session-key']);

        $result = $identity->login([
            'email' => 'user@example.test',
            'password' => 'secret',
        ]);

        $this->assertFalse($result['loggedIn']);
        $this->assertFalse($result['loggedInAs']);
        $this->assertSame('LoginFailed', $result['messages']->current()->getType());
        $this->assertSame([], $identity->getSessionIdentity());
    }

    public function testLoginRejectsDisabledAndWrongPasswords(): void
    {
        $identity = $this->createLoginManager($this->createLoginUser(42, null, true, false));
        $identity->setClaim(['key' => 'unit-session-key']);

        $disabledResult = $identity->login([
            'email' => 'user@example.test',
            'password' => 'secret',
        ]);

        $this->assertFalse($disabledResult['loggedIn']);
        $this->assertSame('LoginFailed', $disabledResult['messages']->current()->getType());

        $identity = $this->createLoginManager($this->createLoginUser(42, 'hashed-password', false, false));
        $identity->setClaim(['key' => 'unit-session-key']);

        $wrongPasswordResult = $identity->login([
            'email' => 'user@example.test',
            'password' => 'secret',
        ]);

        $this->assertFalse($wrongPasswordResult['loggedIn']);
        $this->assertSame('LoginFailed', $wrongPasswordResult['messages']->current()->getType());
    }

    public function testLoginStoresSessionIdentityWhenCredentialsAreValid(): void
    {
        $user = $this->createLoginUser(42, 'hashed-password', true, false);
        $identity = $this->createLoginManager($user);
        $identity->setClaim(['key' => 'unit-session-key']);

        $result = $identity->login([
            'email' => 'user@example.test',
            'password' => 'secret',
        ]);

        $this->assertTrue($result['loggedIn']);
        $this->assertFalse($result['loggedInAs']);
        $this->assertSame(0, $result['messages']->count());
        $this->assertSame(['userId' => 42], $identity->getSessionIdentity());
    }

    public function testStatelessLoginReturnsUpdatedJwtWithClaimIdentity(): void
    {
        $session = $this->createSession();
        $jwt = $this->createJwtService();
        $user = $this->createLoginUser(42, 'hashed-password', true, false);
        $identity = $this->createLoginManager(
            $user,
            config: ['identity' => ['stateless' => true]],
            session: $session,
            request: $this->createRequest(),
            jwt: $jwt,
            security: $this->createSecurity(uuids: ['login-key'])
        );

        $result = $identity->login([
            'email' => 'user@example.test',
            'password' => 'secret',
        ]);

        $this->assertTrue($result['loggedIn']);
        $this->assertFalse($result['loggedInAs']);
        $this->assertSame(0, $result['messages']->count());
        $this->assertSame('token:' . Manager::SESSION_KEY, $result['jwt']);
        $this->assertSame('token:' . Manager::SESSION_KEY . Manager::REFRESH_SUFFIX, $result['refreshToken']);
        $this->assertFalse($result['refreshed']);
        $this->assertSame(['userId' => 42, 'key' => 'login-key'], $identity->claim);
        $this->assertSame('{"userId":42,"key":"login-key"}', $jwt->builderOptions[0]['subject']);
        $this->assertSame([], $session->data);
    }

    public function testLoginRejectsDeletedUsers(): void
    {
        $user = $this->createLoginUser(42, 'hashed-password', true, true);
        $identity = $this->createLoginManager($user);
        $identity->setClaim(['key' => 'unit-session-key']);

        $result = $identity->login([
            'email' => 'user@example.test',
            'password' => 'secret',
        ]);

        $this->assertFalse($result['loggedIn']);
        $this->assertSame('LoginForbidden', $result['messages']->current()->getType());
        $this->assertSame([], $identity->getSessionIdentity());
    }

    public function testResetReturnsMessagesForInvalidEmailAndDisabledConfig(): void
    {
        $identity = $this->createResetManager(config: [
            'identity' => [
                'resetPassword' => [
                    'disable' => true,
                ],
            ],
        ]);

        $result = $identity->reset(['email' => 'not-an-email']);

        $this->assertArrayHasKey('messages', $result);
        $this->assertContains('ResetPasswordDisabled', $this->messageTypes($result['messages']));
    }

    public function testResetDoesNotRevealUnknownEmails(): void
    {
        $identity = $this->createResetManager();

        $this->assertSame([], $identity->reset(['email' => 'unknown@example.test']));
    }

    public function testResetCreatesTokenAndStoresHashedValue(): void
    {
        $state = [];
        $user = $this->createResetUser($state);
        $identity = $this->createResetManager(
            $user,
            security: $this->createSecurity(base64: ['plain-reset-token'])
        );

        $result = $identity->reset(['email' => 'user@example.test']);

        $this->assertSame([], $result);
        $this->assertSame('hash:plain-reset-token:user@example.test', $state['resetToken']);
        $this->assertSame(1, $state['saved']);
    }

    public function testResetReturnsModelMessagesWhenTokenSaveFails(): void
    {
        $state = [];
        $messages = [new Message('save failed', 'resetToken', 'SaveFailed')];
        $user = $this->createResetUser($state, save: false, messages: $messages);
        $identity = $this->createResetManager(
            $user,
            security: $this->createSecurity(base64: ['plain-reset-token'])
        );

        $result = $identity->reset(['email' => 'user@example.test']);

        $this->assertSame($messages, $result['messages']);
        $this->assertSame(1, $state['saved']);
    }

    public function testResetRejectsInvalidPasswordResetToken(): void
    {
        $state = [];
        $user = $this->createResetUser($state, tokenMatches: false);
        $identity = $this->createResetManager($user);

        $result = $identity->reset([
            'email' => 'user@example.test',
            'resetToken' => 'plain-reset-token',
            'password' => 'new-secret',
        ]);

        $this->assertContains('NotValid', $this->messageTypes($result['messages']));
        $this->assertSame(0, $state['saved']);
        $this->assertSame('stored-reset-token', $state['resetToken']);
    }

    public function testResetUpdatesPasswordWhenTokenIsValid(): void
    {
        $state = [];
        $user = $this->createResetUser($state);
        $identity = $this->createResetManager($user);

        $result = $identity->reset([
            'email' => 'user@example.test',
            'resetToken' => 'plain-reset-token',
            'password' => 'new-secret',
        ]);

        $this->assertSame([], $result);
        $this->assertNull($state['resetToken']);
        $this->assertSame('new-secret', $state['password']);
        $this->assertSame(1, $state['saved']);
    }

    public function testResetReturnsModelMessagesWhenPasswordSaveFails(): void
    {
        $state = [];
        $messages = [new Message('save failed', 'password', 'SaveFailed')];
        $user = $this->createResetUser($state, save: false, messages: $messages);
        $identity = $this->createResetManager($user);

        $result = $identity->reset([
            'email' => 'user@example.test',
            'resetToken' => 'plain-reset-token',
            'password' => 'new-secret',
        ]);

        $this->assertSame($messages, $result['messages']);
        $this->assertNull($state['resetToken']);
        $this->assertSame('new-secret', $state['password']);
        $this->assertSame(1, $state['saved']);
    }

    public function testJwtTokenUsesRequestUriDefaultsAndConfiguredOptions(): void
    {
        $jwt = $this->createJwtService();
        $identity = $this->createManager(
            request: $this->createRequest(scheme: 'https', host: 'api.example.test'),
            jwt: $jwt
        );

        $token = $identity->getJwtToken('unit-id', ['userId' => 42], ['issuer' => 'configured-issuer']);

        $this->assertSame('token:unit-id', $token);
        $this->assertSame('configured-issuer', $jwt->builderOptions[0]['issuer']);
        $this->assertSame('https://api.example.test', $jwt->builderOptions[0]['audience']);
        $this->assertSame('unit-id', $jwt->builderOptions[0]['id']);
        $this->assertSame('{"userId":42}', $jwt->builderOptions[0]['subject']);
    }

    public function testClaimFromTokenValidatesAndDecodesSubject(): void
    {
        $jwt = $this->createJwtService([
            'valid-token' => ['sub' => '{"key":"decoded-key"}'],
            'scalar-token' => ['sub' => '"not-an-array"'],
        ]);
        $identity = $this->createManager(
            request: $this->createRequest(scheme: 'https', host: 'api.example.test'),
            jwt: $jwt
        );

        $this->assertSame(['key' => 'decoded-key'], $identity->getClaimFromToken('valid-token', 'unit-claim'));
        $this->assertSame([
            'issuer' => 'https://api.example.test',
            'audience' => 'https://api.example.test',
            'id' => 'unit-claim',
        ], $jwt->validated[0]);
        $this->assertSame([], $identity->getClaimFromToken('scalar-token', 'unit-claim'));
        $this->assertSame(['key' => 'decoded-key'], $identity->getClaimFromAuthorization(['Bearer', 'valid-token']));
        $this->assertSame([], $identity->getClaimFromAuthorization(['Basic', 'valid-token']));
    }

    public function testGetClaimReadsEverySupportedSource(): void
    {
        $cached = $this->createManager(request: $this->createRequest(throwJson: true));
        $cached->setClaim(['key' => 'cached-key']);
        $this->assertSame(['key' => 'cached-key'], $cached->getClaim());

        $refresh = $this->createManager(
            options: ['sessionKey' => 'unit-session'],
            request: $this->createRequest(params: ['refreshToken' => 'refresh-token']),
            jwt: $this->createJwtService(['refresh-token' => ['sub' => '{"key":"refresh-key"}']])
        );
        $this->assertSame(['key' => 'refresh-key'], $refresh->getClaim(refresh: true, force: true));

        $jwt = $this->createManager(
            request: $this->createRequest(json: (object)['jwt' => 'jwt-token']),
            jwt: $this->createJwtService(['jwt-token' => ['sub' => '{"key":"jwt-key"}']])
        );
        $this->assertSame(['key' => 'jwt-key'], $jwt->getClaim(force: true));

        $authorization = $this->createManager(
            request: $this->createRequest(headers: ['X-Authorization' => 'Bearer auth-token']),
            jwt: $this->createJwtService(['auth-token' => ['sub' => '{"key":"auth-key"}']])
        );
        $this->assertSame(['key' => 'auth-key'], $authorization->getClaim(force: true));

        $session = $this->createSession();
        $session->set(Manager::SESSION_KEY, ['key' => 'session-key']);
        $fallback = $this->createManager(
            ['identity' => ['sessionFallback' => true]],
            session: $session,
            request: $this->createRequest(throwJson: true)
        );
        $this->assertSame(['key' => 'session-key'], $fallback->getClaim(force: true));

        $statelessFallback = $this->createManager(
            [
                'identity' => [
                    'sessionFallback' => true,
                    'stateless' => true,
                ],
            ],
            session: $session,
            request: $this->createRequest(throwJson: true)
        );
        $this->assertSame([], $statelessFallback->getClaim(force: true));

        $unsupported = $this->createManager(request: $this->createRequest());
        $this->assertSame([], $unsupported->getClaim(force: true));
    }

    public function testGetJwtCreatesClaimAndSessionFallbackTokens(): void
    {
        $session = $this->createSession();
        $jwt = $this->createJwtService();
        $identity = $this->createManager(
            [
                'identity' => [
                    'sessionFallback' => true,
                    'token' => ['expiration' => 100],
                    'refreshToken' => ['expiration' => 200],
                ],
            ],
            session: $session,
            request: $this->createRequest(),
            jwt: $jwt,
            security: $this->createSecurity(uuids: ['created-key'])
        );

        $result = $identity->getJwt();

        $this->assertSame([
            'jwt' => 'token:' . Manager::SESSION_KEY,
            'refreshToken' => 'token:' . Manager::SESSION_KEY . Manager::REFRESH_SUFFIX,
            'refreshed' => false,
        ], $result);
        $this->assertSame(['key' => 'created-key'], $identity->claim);
        $this->assertSame(['key' => 'created-key'], $session->data[Manager::SESSION_KEY]);
        $this->assertSame(100, $jwt->builderOptions[0]['expiration']);
        $this->assertSame(200, $jwt->builderOptions[1]['expiration']);
    }

    public function testGetJwtPreservesStatelessIdentityPayloadWithoutSessionFallback(): void
    {
        $session = $this->createSession();
        $jwt = $this->createJwtService();
        $identity = $this->createManager(
            [
                'identity' => [
                    'stateless' => true,
                    'sessionFallback' => true,
                ],
            ],
            session: $session,
            request: $this->createRequest(),
            jwt: $jwt,
            security: $this->createSecurity(uuids: ['created-key'])
        );
        $identity->setSessionIdentity(['userId' => 42]);

        $result = $identity->getJwt();

        $this->assertSame([
            'jwt' => 'token:' . Manager::SESSION_KEY,
            'refreshToken' => 'token:' . Manager::SESSION_KEY . Manager::REFRESH_SUFFIX,
            'refreshed' => false,
        ], $result);
        $this->assertSame(['userId' => 42, 'key' => 'created-key'], $identity->claim);
        $this->assertSame('{"userId":42,"key":"created-key"}', $jwt->builderOptions[0]['subject']);
        $this->assertSame([], $session->data);
    }

    public function testGetJwtRefreshRotatesClaimAndMovesSessionIdentity(): void
    {
        $session = $this->createSession();
        $session->set('old-key', ['userId' => 42]);
        $identity = $this->createManager(
            ['identity' => ['sessionFallback' => true]],
            session: $session,
            request: $this->createRequest(params: ['refreshToken' => 'refresh-token']),
            jwt: $this->createJwtService(['refresh-token' => ['sub' => '{"key":"old-key"}']]),
            security: $this->createSecurity(uuids: ['new-key'])
        );

        $result = $identity->getJwt(true);

        $this->assertTrue($result['refreshed']);
        $this->assertSame(['key' => 'new-key'], $identity->claim);
        $this->assertArrayNotHasKey('old-key', $session->data);
        $this->assertSame(['userId' => 42], $session->data['new-key']);
        $this->assertSame(['key' => 'new-key'], $session->data[Manager::SESSION_KEY]);
    }

    public function testGetJwtRefreshRotatesStatelessClaimIdentityWithoutSessionStorage(): void
    {
        $session = $this->createSession();
        $jwt = $this->createJwtService([
            'refresh-token' => ['sub' => '{"key":"old-key","userId":42}'],
        ]);
        $identity = $this->createManager(
            ['identity' => ['stateless' => true]],
            session: $session,
            request: $this->createRequest(params: ['refreshToken' => 'refresh-token']),
            jwt: $jwt,
            security: $this->createSecurity(uuids: ['new-key'])
        );

        $result = $identity->getJwt(true);

        $this->assertTrue($result['refreshed']);
        $this->assertSame(['key' => 'new-key', 'userId' => 42], $identity->claim);
        $this->assertSame('{"key":"new-key","userId":42}', $jwt->builderOptions[0]['subject']);
        $this->assertSame([], $session->data);
    }

    public function testUserTraitLoadsCachesFindsAndReportsUsers(): void
    {
        IdentityUserModelDouble::reset();

        $current = $this->createLoginUser(42, 'hash', true, false);
        $impersonator = $this->createLoginUser(7, 'hash', true, false);
        IdentityUserModelDouble::$findFirstWithById = [
            42 => $current,
            7 => $impersonator,
        ];

        $identity = $this->createManager(models: $this->createModelsService());
        $identity->setClaim(['key' => 'unit-session-key']);
        $identity->setSessionIdentity(['userId' => 42, 'asUserId' => 7]);

        $this->assertSame($current, $identity->getUser());
        $this->assertSame($impersonator, $identity->getUserAs());
        $this->assertSame(42, $identity->getUserId());
        $this->assertSame(7, $identity->getUserAsId());
        $this->assertTrue($identity->isLoggedIn());
        $this->assertTrue($identity->isLoggedInAs());
        $this->assertCount(2, IdentityUserModelDouble::$findFirstWithCalls);

        $this->assertSame($current, $identity->getUser());
        $this->assertCount(2, IdentityUserModelDouble::$findFirstWithCalls);

        $this->assertSame($current, $identity->getUser(force: true));
        $this->assertCount(3, IdentityUserModelDouble::$findFirstWithCalls);

        $identity->removeSessionIdentity();
        $identity->setUser(null);
        $identity->setUserAs(null);
        $this->assertNull($identity->getUser());
        $this->assertNull($identity->getUserAs());
    }

    public function testUserTraitFindsUsersThroughConfiguredModelClass(): void
    {
        IdentityUserModelDouble::reset();

        $user = $this->createLoginUser(42, 'hash', true, false);
        IdentityUserModelDouble::$findFirstResult = $user;

        $identity = $this->createManager(models: $this->createModelsService());

        $this->assertSame($user, $identity->findUserById(42));
        $this->assertSame('id = :id:', IdentityUserModelDouble::$findFirstCalls[0][0]);
        $this->assertSame(['id' => 42], IdentityUserModelDouble::$findFirstCalls[0]['bind']);
        $this->assertSame(['id' => Column::BIND_PARAM_INT], IdentityUserModelDouble::$findFirstCalls[0]['bindTypes']);

        $this->assertSame($user, $identity->findUserByEmail('user@example.test'));
        $this->assertSame('email = :email:', IdentityUserModelDouble::$findFirstCalls[1][0]);
        $this->assertSame(['email' => 'user@example.test'], IdentityUserModelDouble::$findFirstCalls[1]['bind']);
        $this->assertSame(['email' => Column::BIND_PARAM_STR], IdentityUserModelDouble::$findFirstCalls[1]['bindTypes']);
    }

    public function testUserTraitListAccessorsReadIdentityPayload(): void
    {
        $identity = new class extends Manager {
            public array $unitIdentity = [];

            public function getIdentity(?array $userExpose = null): array
            {
                return $this->unitIdentity;
            }
        };
        $identity->unitIdentity = [
            'roleList' => ['admin' => true],
            'groupList' => ['staff' => true],
            'typeList' => ['internal' => true],
        ];

        $this->assertSame(['admin' => true], $identity->getRoleList());
        $this->assertSame(['staff' => true], $identity->getGroupList());
        $this->assertSame(['internal' => true], $identity->getTypeList());
    }

    private function createManager(
        array $config = [],
        array $options = [],
        ?object $session = null,
        ?object $bootstrap = null,
        ?object $request = null,
        ?object $jwt = null,
        ?object $security = null,
        ?object $models = null,
    ): Manager {
        return $this->attachIdentityServices(
            new Manager($options),
            $config,
            $session,
            $bootstrap,
            $request,
            $jwt,
            $security,
            $models
        );
    }

    private function createRoleAwareManager(
        array $roleList,
        array $config = [],
        bool $ws = false,
        bool $cli = false,
    ): Manager {
        $identity = new class extends Manager {
            public array $unitRoleList = [];

            public function getRoleList(): array
            {
                return $this->unitRoleList;
            }
        };
        $identity->unitRoleList = $roleList;

        return $this->attachIdentityServices($identity, $config, bootstrap: $this->createBootstrap($ws, $cli));
    }

    private function createLoginManager(
        ?UserInterface $user = null,
        array $config = [],
        ?object $session = null,
        ?object $request = null,
        ?object $jwt = null,
        ?object $security = null,
    ): Manager {
        $identity = new class extends Manager {
            public ?UserInterface $unitUser = null;

            public function findUserByEmail(string $string): ?UserInterface
            {
                return $this->unitUser;
            }

            public function getUser(bool $as = false, ?bool $force = null): ?UserInterface
            {
                if ($as || !$this->unitUser) {
                    return null;
                }

                $sessionIdentity = $this->getSessionIdentity();
                if (($sessionIdentity['userId'] ?? null) !== $this->unitUser->getId()) {
                    return null;
                }

                return $this->unitUser;
            }
        };
        $identity->unitUser = $user;

        return $this->attachIdentityServices(
            $identity,
            $config,
            session: $session,
            request: $request,
            jwt: $jwt,
            security: $security
        );
    }

    private function createResetManager(
        ?UserInterface $user = null,
        array $config = [],
        ?object $security = null,
    ): Manager {
        $identity = new class extends Manager {
            public ?UserInterface $unitUser = null;

            public function findUserByEmail(string $string): ?UserInterface
            {
                return $this->unitUser;
            }
        };
        $identity->unitUser = $user;

        return $this->attachIdentityServices($identity, $config, security: $security ?? $this->createSecurity());
    }

    private function attachIdentityServices(
        Manager $identity,
        array $config = [],
        ?object $session = null,
        ?object $bootstrap = null,
        ?object $request = null,
        ?object $jwt = null,
        ?object $security = null,
        ?object $models = null,
    ): Manager {
        $di = new Di();
        $di->set('config', new Config($config));
        $di->set('session', $session ?? $this->createSession());
        $di->set('bootstrap', $bootstrap ?? $this->createBootstrap());
        if ($request) {
            $di->set('request', $request);
        }
        if ($jwt) {
            $di->set('jwt', $jwt);
        }
        if ($security) {
            $di->set('security', $security);
        }
        if ($models) {
            $di->set('models', $models);
        }
        $di->set('identity', $identity);
        $identity->setDI($di);

        return $identity;
    }

    private function createSession(): object
    {
        return new class {
            public array $data = [];

            public function set(string $key, mixed $value): void
            {
                $this->data[$key] = $value;
            }

            public function get(string $key): mixed
            {
                return $this->data[$key] ?? null;
            }

            public function has(string $key): bool
            {
                return array_key_exists($key, $this->data);
            }

            public function remove(string $key): void
            {
                unset($this->data[$key]);
            }
        };
    }

    private function createBootstrap(bool $ws = false, bool $cli = false): object
    {
        return new class ($ws, $cli) {
            public function __construct(private bool $ws, private bool $cli)
            {
            }

            public function isWs(): bool
            {
                return $this->ws;
            }

            public function isCli(): bool
            {
                return $this->cli;
            }
        };
    }

    private function createRequest(
        array $params = [],
        array $headers = [],
        ?object $json = null,
        bool $throwJson = false,
        string $scheme = 'https',
        string $host = 'unit.test',
    ): object {
        return new class ($params, $headers, $json, $throwJson, $scheme, $host) {
            public function __construct(
                private array $params,
                private array $headers,
                private ?object $json,
                private bool $throwJson,
                private string $scheme,
                private string $host,
            ) {
            }

            public function get(string $name, mixed $filters = null, mixed $default = null): mixed
            {
                return $this->params[$name] ?? $default;
            }

            public function getHeader(string $key): string
            {
                return $this->headers[$key] ?? '';
            }

            public function getJsonRawBody(): object
            {
                if ($this->throwJson) {
                    throw new \InvalidArgumentException('invalid json');
                }

                return $this->json ?? new \stdClass();
            }

            public function getScheme(): string
            {
                return $this->scheme;
            }

            public function getHttpHost(): string
            {
                return $this->host;
            }
        };
    }

    private function createJwtService(array $claimsByToken = []): object
    {
        return new class ($claimsByToken) {
            public array $builderOptions = [];

            public array $validated = [];

            public function __construct(private array $claimsByToken)
            {
            }

            public function builder(array $options): object
            {
                $this->builderOptions[] = $options;

                return new class ($options) {
                    public function __construct(private array $options)
                    {
                    }

                    public function getToken(): object
                    {
                        return new class ($this->options) {
                            public function __construct(private array $options)
                            {
                            }

                            public function getToken(): string
                            {
                                return 'token:' . $this->options['id'];
                            }
                        };
                    }
                };
            }

            public function parseToken(string $token): object
            {
                return new class ($this->claimsByToken[$token] ?? []) {
                    public function __construct(private array $claims)
                    {
                    }

                    public function getClaims(): object
                    {
                        return new class ($this->claims) {
                            public function __construct(private array $claims)
                            {
                            }

                            public function has(string $key): bool
                            {
                                return array_key_exists($key, $this->claims);
                            }

                            public function get(string $key): mixed
                            {
                                return $this->claims[$key] ?? null;
                            }
                        };
                    }
                };
            }

            public function validateToken(object $token, int $leeway, array $validators): void
            {
                $this->validated[] = $validators;
            }
        };
    }

    private function createSecurity(array $uuids = ['unit-uuid'], array $base64 = ['unit-token']): object
    {
        return new class ($uuids, $base64) {
            public function __construct(public array $uuids, public array $base64)
            {
            }

            public function getRandom(): object
            {
                return new class ($this) {
                    public function __construct(private object $security)
                    {
                    }

                    public function uuid(): string
                    {
                        return array_shift($this->security->uuids) ?? 'unit-uuid';
                    }

                    public function base64Safe(int $length): string
                    {
                        return array_shift($this->security->base64) ?? 'unit-token';
                    }
                };
            }
        };
    }

    private function createModelsService(): object
    {
        return new class {
            public function getUser(): string
            {
                return IdentityUserModelDouble::class;
            }
        };
    }

    private function createKeyedEntity(string $key): object
    {
        return new class ($key) {
            public function __construct(private string $key)
            {
            }

            public function getKey(): string
            {
                return $this->key;
            }
        };
    }

    /**
     * @param array<string, mixed>|null $exposed
     * @param array<string, mixed> $loadedRelations
     * @param array<string, mixed> $dirtyRelations
     */
    private function createUserDouble(
        ?array $exposed = null,
        array $loadedRelations = [],
        array $dirtyRelations = [],
    ): UserInterface {
        $user = $this->createStub(UserInterface::class);
        $user
            ->method('expose')
            ->willReturnCallback(static fn (?array $columns = null): array => $exposed ?? []);
        $user
            ->method('hasLoadedRelatedAlias')
            ->willReturnCallback(static fn (string $alias): bool => array_key_exists($alias, $loadedRelations));
        $user
            ->method('getLoadedRelatedAlias')
            ->willReturnCallback(static fn (string $alias): mixed => $loadedRelations[$alias] ?? null);
        $user
            ->method('hasDirtyRelatedAlias')
            ->willReturnCallback(static fn (string $alias): bool => array_key_exists($alias, $dirtyRelations));
        $user
            ->method('getDirtyRelatedAlias')
            ->willReturnCallback(static fn (string $alias): mixed => $dirtyRelations[$alias] ?? null);

        return $user;
    }

    private function createLoginUser(int $id, ?string $password, bool $passwordMatches, bool $deleted): UserInterface
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getId')->willReturn($id);
        $user->method('getPassword')->willReturn($password);
        $user->method('checkHash')->willReturn($passwordMatches);
        $user->method('isDeleted')->willReturn($deleted);
        $user->method('hasLoadedRelatedAlias')->willReturn(false);
        $user->method('hasDirtyRelatedAlias')->willReturn(false);

        return $user;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function createResetUser(
        array &$state,
        bool $save = true,
        bool $tokenMatches = true,
        ?array $messages = null,
    ): UserInterface {
        $state = array_merge([
            'email' => 'user@example.test',
            'resetToken' => 'stored-reset-token',
            'password' => null,
            'saved' => 0,
        ], $state);
        $messages ??= [];

        $user = $this->createStub(UserInterface::class);
        $user->method('getEmail')->willReturnCallback(static fn (): mixed => $state['email']);
        $user->method('getResetToken')->willReturnCallback(static fn (): mixed => $state['resetToken']);
        $user
            ->method('setResetToken')
            ->willReturnCallback(static function (mixed $token) use (&$state): void {
                $state['resetToken'] = $token;
            });
        $user
            ->method('setPassword')
            ->willReturnCallback(static function (mixed $password) use (&$state): void {
                $state['password'] = $password;
            });
        $user
            ->method('hash')
            ->willReturnCallback(static fn (mixed $value, mixed $salt = null): string => 'hash:' . $value . ':' . $salt);
        $user->method('checkHash')->willReturn($tokenMatches);
        $user
            ->method('save')
            ->willReturnCallback(static function () use (&$state, $save): bool {
                $state['saved']++;
                return $save;
            });
        $user->method('getMessages')->willReturn($messages);

        return $user;
    }

    /**
     * @return string[]
     */
    private function messageTypes(Messages $messages): array
    {
        $types = [];
        foreach ($messages as $message) {
            $types[] = $message->getType();
        }

        return $types;
    }
}
