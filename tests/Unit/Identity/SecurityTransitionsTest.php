<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity;

use RuntimeException;
use PhalconKit\Tests\Unit\Identity\Fixtures\SecurityTransitionUsers;
use PhalconKit\Exception\ConfigurationException;
use Phalcon\Acl\Adapter\Memory;
use Phalcon\Di\Di;
use Phalcon\Filter\FilterFactory;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Config\Config;
use PhalconKit\Encryption\Security;
use PhalconKit\Exception\HttpException;
use PhalconKit\Http\Request;
use PhalconKit\Identity\Manager;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Mvc\Model\Behavior\Security as ModelSecurity;
use PhalconKit\Provider\Jwt\Jwt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Exercises identity and model authorization together using synthetic users and storage. */
final class SecurityTransitionsTest extends TestCase
{
    private const string LEGACY_PUBLIC_KEY = 'Tf0PHY/^yDdJs*~)?x#xCNj_N[jW/`c*';

    private Config $defaults;
    private ?\Phalcon\Di\DiInterface $previousDi;
    private string $secret;
    private array $server;
    private array $request;

    protected function setUp(): void
    {
        $this->previousDi = Di::getDefault();
        $this->server = $_SERVER;
        $this->request = $_REQUEST;
        $_SERVER = ['HTTP_HOST' => 'security-audit.test', 'HTTPS' => 'on', 'REQUEST_METHOD' => 'GET'];
        $_REQUEST = [];
        SecurityTransitionUsers::$users = [];
        SecurityTransitionUsers::$throw = false;
        ModelSecurity::setRoles(null);
        ModelSecurity::setAcl(null);
        ModelSecurity::staticStop();
        ModelSecurity::staticEnable();
        $this->defaults = new \PhalconKit\Bootstrap\Config();
        $this->secret = 'Synthetic!A9-' . bin2hex(random_bytes(64));
        $this->user(7, 'admin');
        $this->user(42, 'member');
        $this->user(99, 'member');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_REQUEST = $this->request;
        ModelSecurity::setRoles(null);
        ModelSecurity::setAcl(null);
        ModelSecurity::staticStop();
        Di::reset();
        if ($this->previousDi !== null) {
            Di::setDefault($this->previousDi);
        }
    }

    public static function modes(): array
    {
        return ['stateful' => [false], 'stateless' => [true]];
    }

    #[DataProvider('modes')]
    public function testValidAccessControl(bool $stateless): void
    {
        $identity = $this->manager($stateless);
        $_REQUEST['jwt'] = $this->token(['key' => 'audit-key', 'userId' => 42]);
        self::assertTrue($identity->isLoggedIn());
        self::assertSame(42, $identity->getUserId());
    }

    public function testConfiguredSecretRejectsPublicDefaultKeyControl(): void
    {
        $identity = $this->manager(true);
        $_REQUEST['jwt'] = $this->token(['key' => 'forged-key', 'userId' => 7], self::LEGACY_PUBLIC_KEY);
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $identity->isLoggedIn();
    }

    public function testDefaultSecretMustNotPermitForgedStatelessAdminIdentity(): void
    {
        $publicDefault = self::LEGACY_PUBLIC_KEY;
        $identity = $this->manager(true, $publicDefault);
        $_REQUEST['jwt'] = $this->token(['key' => 'forged-key', 'userId' => 7], $publicDefault);
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $identity->isLoggedIn();
    }

    #[DataProvider('modes')]
    public function testDeletedUserMustNotAuthenticateFromExistingToken(bool $stateless): void
    {
        $this->user(42, 'member', true);
        $identity = $this->manager($stateless);
        $_REQUEST['jwt'] = $this->token(['key' => 'audit-key', 'userId' => 42]);
        self::assertFalse($identity->isLoggedIn(), 'The identity consumer accepted a user marked deleted.');
    }

    public function testDeletedUserPasswordLoginIsRejectedControl(): void
    {
        $this->user(42, 'member', true);
        $identity = $this->manager(false);
        $identity->setClaim(['key' => 'empty-key']);
        $result = $identity->login(['email' => 'user42@example.test', 'password' => 'Synthetic-password!42']);
        self::assertFalse($result['loggedIn']);
        self::assertSame(403, $result['messages'][0]->getCode());
    }

    public function testStatelessLogoutAsMustClearImpersonationMarker(): void
    {
        $identity = $this->manager(true);
        $_REQUEST['jwt'] = $this->token(['key' => 'audit-key', 'userId' => 42, 'asUserId' => 7]);
        $result = $identity->logoutAs();
        self::assertSame(7, $identity->getUserId());
        self::assertFalse($result['loggedInAs'], 'Returning to the original user retained asUserId in the replacement token.');
    }

    public function testStatelessNewLoginMustNotRetainPreviousAdminReturnCapability(): void
    {
        $identity = $this->manager(true);
        $_REQUEST['jwt'] = $this->token(['key' => 'audit-key', 'userId' => 42, 'asUserId' => 7]);
        $identity->logoutAs();
        $result = $identity->login(['email' => 'user99@example.test', 'password' => 'Synthetic-password!99']);
        self::assertTrue($result['loggedIn']);
        self::assertSame(99, $identity->getUserId());
        // Simulate a fresh request using only the token issued for user 99.
        $_REQUEST['jwt'] = $result['jwt'];
        $nextRequest = $this->manager(true);
        self::assertSame(99, $nextRequest->getUserId());
        $nextRequest->logoutAs();
        self::assertSame(99, $nextRequest->getUserId(), 'The newly authenticated member token restored the previous admin identity.');
    }

    #[DataProvider('modes')]
    public function testIdentityReplacementInvalidatesCachedUsersAndRoles(bool $stateless): void
    {
        $identity = $this->manager($stateless);
        $identity->setClaim(['key' => 'audit-key']);
        $identity->setSessionIdentity(['userId' => 7, 'asUserId' => 42]);
        self::assertSame(7, $identity->getUserId());
        self::assertSame(42, $identity->getUserAsId());
        self::assertArrayHasKey('admin', ModelSecurity::getRoles());

        $identity->setSessionIdentity(['userId' => 99]);
        self::assertSame(99, $identity->getUserId());
        self::assertNull($identity->getUserAsId());
        self::assertArrayNotHasKey('admin', ModelSecurity::getRoles());

        $identity->removeSessionIdentity();
        self::assertNull($identity->getUserId());
        self::assertArrayHasKey('guest', ModelSecurity::getRoles());
    }

    public function testLogoutMustInvalidateCachedModelAuthorization(): void
    {
        $identity = $this->manager(true);
        $_REQUEST['jwt'] = $this->token(['key' => 'audit-key', 'userId' => 7]);
        $model = $this->createStub(ModelInterface::class);
        $acl = new Memory();
        $acl->setDefaultAction(0);
        $acl->addRole('admin');
        $acl->addRole('everyone');
        $acl->addRole('guest');
        $acl->addComponent(get_class($model), ['update']);
        $acl->allow('admin', get_class($model), 'update');
        ModelSecurity::setAcl($acl);
        $behavior = new ModelSecurity();
        self::assertTrue($behavior->notify('beforeUpdate', $model));
        $result = $identity->logout();
        self::assertFalse($result['loggedIn']);
        self::assertNull($identity->getUserId());
        self::assertFalse($behavior->notify('beforeUpdate', $model), 'Model ACL still allowed an admin operation after logout.');
    }

    public function testFailedIdentityLookupMustRestoreModelSecurityChecks(): void
    {
        $identity = $this->manager(true);
        $_REQUEST['jwt'] = $this->token(['key' => 'audit-key', 'userId' => 42]);
        SecurityTransitionUsers::$throw = true;
        try {
            $identity->getUser();
            self::fail('Synthetic lookup should throw.');
        } catch (RuntimeException $error) {
            self::assertSame('Synthetic user lookup failure', $error->getMessage());
        }
        $model = $this->createStub(ModelInterface::class);
        $acl = new Memory();
        $acl->setDefaultAction(0);
        $acl->addRole('guest');
        $acl->addComponent(get_class($model), ['update']);
        ModelSecurity::setAcl($acl);
        ModelSecurity::setRoles(['guest']);
        self::assertFalse((new ModelSecurity())->notify('beforeUpdate', $model), 'Model security returned null and skipped a denied operation after the lookup exception.');
    }

    public function testMissingSecretRejectsIssuanceAndAllowsAnonymousLookup(): void
    {
        $identity = $this->manager(true, '');
        self::assertFalse($identity->isLoggedIn());
        $this->expectException(ConfigurationException::class);
        $identity->getJwt();
    }

    public function testMissingSecretRejectsForgedIdentity(): void
    {
        $identity = $this->manager(true, '');
        $_REQUEST['jwt'] = $this->token(['key' => 'forged-key', 'userId' => 7], '');
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $identity->isLoggedIn();
    }

    public function testDeletedUserCannotBeImpersonated(): void
    {
        $this->user(42, 'member', true);
        $identity = $this->manager(true);
        $_REQUEST['jwt'] = $this->token(['key' => 'audit-key', 'userId' => 7]);
        $result = $identity->loginAs(['userId' => 42]);
        self::assertSame(7, $identity->getUserId());
        self::assertFalse($result['loggedInAs']);
        self::assertArrayNotHasKey('jwt', $result);
    }

    public function testNestedIdentityLookupPreservesOuterSecurityGuard(): void
    {
        $identity = $this->manager(true);
        $_REQUEST['jwt'] = $this->token(['key' => 'audit-key', 'userId' => 42]);
        ModelSecurity::staticStart();
        self::assertSame(42, $identity->getUserId());
        self::assertTrue(ModelSecurity::getStaticProgress());
    }

    public function testAclFailureRestoresSecurityChecks(): void
    {
        $behavior = $this->getMockBuilder(ModelSecurity::class)->onlyMethods(['isAllowed'])->getMock();
        $behavior->expects(self::exactly(2))->method('isAllowed')->willReturnCallback(
            static function () use (&$failed): bool {
                if (!isset($failed)) {
                    $failed = true;
                    throw new RuntimeException('Synthetic ACL failure');
                }
                return false;
            }
        );
        $model = $this->createStub(ModelInterface::class);
        try {
            $behavior->notify('beforeUpdate', $model);
            self::fail('Expected ACL failure');
        } catch (RuntimeException $error) {
            self::assertSame('Synthetic ACL failure', $error->getMessage());
        }
        self::assertFalse($behavior->notify('beforeUpdate', $model));
    }

    private function user(int $id, string $role, bool $deleted = false): void
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturn("user{$id}@example.test");
        $user->method('isDeleted')->willReturn($deleted);
        $hash = password_hash("Synthetic-password!{$id}", PASSWORD_BCRYPT, ['cost' => 4]);
        $user->method('getPassword')->willReturn($hash);
        $user->method('checkHash')->willReturnCallback(static fn ($hash, $value, $max = 0): bool => password_verify($value, $hash));
        $roleEntity = new class ($role) {
            public function __construct(private string $role)
            {
            }
            public function getKey(): string
            {
                return $this->role;
            }
        };
        $user->method('hasLoadedRelatedAlias')->willReturnCallback(static fn (string $alias): bool => strtolower($alias) === 'rolelist');
        $user->method('getLoadedRelatedAlias')->willReturn([$roleEntity]);
        SecurityTransitionUsers::$users[$id] = $user;
    }

    private function manager(bool $stateless, ?string $secret = null): Manager
    {
        $di = new \PhalconKit\Di\Di();
        Di::setDefault($di);
        $di->setShared('config', new Config(['identity' => [
            'stateless' => $stateless, 'sessionFallback' => false,
            'token' => ['expiration' => time() + 3600],
            'refreshToken' => ['expiration' => time() + 7200],
        ]]));
        $di->setShared('request', new Request());
        $di->setShared('filter', new FilterFactory()->newInstance());
        $di->setShared('security', new Security());
        $di->setShared('jwt', new Jwt(['passphrase' => $secret ?? $this->secret]));
        $di->setShared('models', new class {
            public function getUser(): string
            {
                return SecurityTransitionUsers::class;
            }
        });
        $di->setShared('bootstrap', new class {
            public function isCli(): bool
            {
                return false;
            }
            public function isWs(): bool
            {
                return false;
            }
        });
        $di->setShared('session', new class {
            use \PhalconKit\Tests\Unit\Identity\Fixtures\SessionLifecycleDouble;

            public array $data = ['audit-key' => ['userId' => 42]];
            public function get(string $key): mixed
            {
                return $this->data[$key] ?? null;
            }
            public function has(string $key): bool
            {
                return isset($this->data[$key]);
            }
            public function set(string $key, mixed $value): void
            {
                $this->data[$key] = $value;
            }
            public function remove(string $key): void
            {
                unset($this->data[$key]);
            }
        });
        $identity = new Manager();
        $identity->setDI($di);
        $di->setShared('identity', $identity);
        return $identity;
    }

    private function token(array $subject, ?string $secret = null): string
    {
        $payload = [
            'iss' => 'https://security-audit.test', 'aud' => ['https://security-audit.test'],
            'jti' => Manager::SESSION_KEY, 'iat' => time() - 120, 'nbf' => time() - 120,
            'exp' => time() + 3600, 'sub' => json_encode($subject, JSON_THROW_ON_ERROR),
        ];
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $input = $encode('{"typ":"JWT","alg":"HS512"}') . '.' . $encode(json_encode($payload, JSON_THROW_ON_ERROR));
        return $input . '.' . $encode(hash_hmac('sha512', $input, $secret ?? $this->secret, true));
    }
}
