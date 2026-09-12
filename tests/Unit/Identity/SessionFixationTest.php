<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity;

use Phalcon\Filter\FilterFactory;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Session\Manager as SessionManager;
use Phalcon\Session\ManagerInterface;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Encryption\Security;
use PhalconKit\Exception\HttpException;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Http\Request;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Provider\Jwt\Jwt;
use PhalconKit\Tests\Unit\Identity\Fixtures\SessionIdentityManager;
use PhalconKit\Tests\Unit\Identity\Fixtures\SessionOauth2Double;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/** Real PHP session persistence and cookie replay; users and OAuth account storage are synthetic. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SessionFixationTest extends TestCase
{
    private string $directory;
    private Di $di;
    private SessionManager $session;
    private SessionIdentityManager $identity;
    private array $users;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/phalconkit-session-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        ini_set('session.use_cookies', '0');
        ini_set('session.cache_limiter', '');
        ini_set('session.use_strict_mode', '0');
        $_SERVER = ['HTTP_HOST' => 'session.example.test', 'HTTPS' => 'on', 'REQUEST_METHOD' => 'POST'];
        $_REQUEST = $_GET = $_POST = [];
        $this->session = $this->openSession('synthetic-anonymous-id');
        $this->session->set('cart', ['synthetic-item']);
        $hash = password_hash('Synthetic password!', PASSWORD_BCRYPT, ['cost' => 4]);
        foreach ([42, 7] as $id) {
            $user = $this->createStub(UserInterface::class);
            $user->method('getId')->willReturn($id);
            $user->method('getPassword')->willReturn($hash);
            $user->method('checkHash')->willReturnCallback(static fn (string $stored, string $password): bool => password_verify($password, $stored));
            $user->method('isDeleted')->willReturn(false);
            $this->users[$id] = $user;
        }
        $this->di = new Di();
        Di::setDefault($this->di);
        $this->di->setShared('filter', new FilterFactory()->newInstance());
        $this->di->setShared('security', new Security());
        $this->di->setShared('jwt', new Jwt(['passphrase' => 'Synthetic!A9-' . bin2hex(random_bytes(64))]));
        $this->configure();
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
        Di::reset();
    }

    public static function fallbackModes(): array
    {
        return ['cookie fallback' => [true], 'JWT required' => [false]];
    }

    #[DataProvider('fallbackModes')]
    public function testLoginRefreshAndLogoutPreserveValidFlowsAndRejectOldCookies(bool $fallback): void
    {
        $this->configure(fallback: $fallback);
        $anonymous = $this->anonymousTokens();
        $oldId = $this->session->getId();
        self::assertTrue($this->login()['loggedIn']);
        $loginId = $this->session->getId();
        self::assertNotSame($oldId, $loginId);
        self::assertSame(['synthetic-item'], $this->session->get('cart'));

        $this->request($oldId, ['jwt' => $anonymous['jwt']]);
        self::assertFalse($this->identity->isLoggedIn());
        $this->request($loginId, ['jwt' => $anonymous['jwt']]);
        self::assertTrue($this->identity->isLoggedIn());
        $_REQUEST = ['refreshToken' => $anonymous['refreshToken']];
        $refreshed = $this->identity->getJwt(true);
        $refreshId = $this->session->getId();
        self::assertTrue($refreshed['refreshed']);
        self::assertNotSame($loginId, $refreshId);
        self::assertSame(['userId' => 42], $this->identity->getSessionIdentity());

        $this->request($loginId, ['jwt' => $refreshed['jwt']]);
        self::assertFalse($this->identity->isLoggedIn());
        $this->request($refreshId, ['jwt' => $refreshed['jwt']]);
        self::assertTrue($this->identity->isLoggedIn());
        self::assertFalse($this->identity->logout()['loggedIn']);
        self::assertSame(['synthetic-item'], $this->session->get('cart'));
    }

    public function testFailedLoginAndInvalidRefreshLeaveSessionUnchanged(): void
    {
        $this->anonymousTokens();
        $oldId = $this->session->getId();
        $before = $_SESSION;
        self::assertFalse($this->identity->login(['email' => 'synthetic@example.test', 'password' => 'wrong'])['loggedIn']);
        self::assertSame($oldId, $this->session->getId());
        self::assertSame($before, $_SESSION);
        self::assertTrue($this->login()['loggedIn']);
        $oldId = $this->session->getId();
        $before = $_SESSION;
        $_REQUEST = ['refreshToken' => 'malformed-synthetic-token'];
        try {
            $this->identity->getJwt(true);
            self::fail('Invalid refresh must fail before changing the PHP session.');
        } catch (HttpException $exception) {
            self::assertSame(401, $exception->getCode());
        }
        self::assertSame($oldId, $this->session->getId());
        self::assertSame($before, $_SESSION);
    }

    public function testImpersonationAndReturnInvalidatePriorSessionIdentity(): void
    {
        $anonymous = $this->anonymousTokens();
        $this->login();
        $loginId = $this->session->getId();
        self::assertFalse($this->identity->loginAs(['userId' => 999])['loggedInAs']);
        self::assertSame($loginId, $this->session->getId());
        self::assertTrue($this->identity->loginAs(['userId' => 7])['loggedInAs']);
        $impersonationId = $this->session->getId();
        self::assertNotSame($loginId, $impersonationId);
        self::assertSame(['userId' => 7, 'asUserId' => 42], $this->identity->getSessionIdentity());
        self::assertFalse($this->identity->logoutAs()['loggedInAs']);
        $restoredId = $this->session->getId();
        self::assertNotSame($impersonationId, $restoredId);
        foreach ([$loginId, $impersonationId] as $obsoleteId) {
            $this->request($obsoleteId, ['jwt' => $anonymous['jwt']]);
            self::assertFalse($this->identity->isLoggedIn());
            self::assertFalse($this->identity->isLoggedInAs());
        }
        $this->request($restoredId, ['jwt' => $anonymous['jwt']]);
        self::assertSame(['userId' => 42], $this->identity->getSessionIdentity());
        self::assertTrue($this->identity->isLoggedIn());
    }

    public function testOAuthLoginRenewsSessionBeforeEstablishingIdentity(): void
    {
        class_alias(SessionOauth2Double::class, 'PhalconKit\\Models\\Oauth2');
        $this->anonymousTokens();
        $oldId = $this->session->getId();
        $result = $this->identity->oauth2('synthetic', 'synthetic-user', 'synthetic-provider-token');
        self::assertTrue($result['saved']);
        self::assertTrue($result['loggedIn']);
        self::assertNotSame($oldId, $this->session->getId());
        self::assertSame(['synthetic-item'], $this->session->get('cart'));
        $this->request($oldId);
        self::assertFalse($this->identity->isLoggedIn());
    }

    public function testDirectSsoIdentityAssignmentAlsoRejectsCookieFixation(): void
    {
        $this->anonymousTokens();
        $oldId = $this->session->getId();
        $this->identity->setSessionIdentity(['userId' => 42]);
        self::assertNotSame($oldId, $this->session->getId());
        self::assertTrue($this->identity->isLoggedIn());
        $this->request($oldId);
        self::assertFalse($this->identity->isLoggedIn());
    }

    public static function renewalFailures(): array
    {
        return ['unchanged ID' => ['unchanged'], 'exception' => ['throw'], 'inactive session' => ['inactive']];
    }

    #[DataProvider('renewalFailures')]
    public function testRenewalFailureNeverWritesElevatedIdentity(string $mode): void
    {
        $this->anonymousTokens();
        $key = $this->identity->getKey();
        $this->session->set($key, ['userId' => 7]);
        $before = $_SESSION;
        $oldId = $this->session->getId();
        $session = new class extends SessionManager {
            public string $mode;

            public function exists(): bool
            {
                return $this->mode !== 'inactive' && parent::exists();
            }

            public function regenerateId(bool $deleteOldSession = true): ManagerInterface
            {
                if ($this->mode === 'throw') {
                    throw new \RuntimeException('Synthetic regeneration failure');
                }
                return $this;
            }
        };
        $session->mode = $mode;
        $this->di->remove('session');
        $this->di->setShared('session', $session);
        $this->identity = $this->newIdentity();
        self::assertNotNull($this->identity->getKey());
        self::assertSame($session, $this->identity->session);
        try {
            $this->identity->setSessionIdentity(['userId' => 42]);
            self::fail('Unsuccessful session renewal must reject the replacement.');
        } catch (ServiceException $exception) {
            self::assertStringNotContainsString('Synthetic', $exception->getMessage());
        }
        self::assertSame($oldId, $this->session->getId());
        self::assertSame($before, $_SESSION);
    }

    public function testStatelessIdentityDoesNotResolvePhpSession(): void
    {
        $this->configure(stateless: true);
        $this->di->remove('session');
        $this->di->setShared('session', static function (): never {
            throw new \LogicException('Stateless identity must not resolve PHP sessions.');
        });
        $this->anonymousTokens();
        $before = $_SESSION;
        $oldId = $this->session->getId();
        $login = $this->login();
        self::assertTrue($login['loggedIn']);
        self::assertSame(42, $this->identity->getClaimFromToken($login['jwt'], $this->identity->getSessionKey())['userId']);
        self::assertSame($oldId, $this->session->getId());
        self::assertSame($before, $_SESSION);
    }

    public function testCustomIdentityStorageDoesNotAcquirePhpSessionDependency(): void
    {
        $this->configure(fallback: false);
        $this->di->remove('session');
        $this->di->setShared('session', static function (): never {
            throw new \LogicException('Custom identity storage must not resolve PHP sessions.');
        });
        $this->identity = $this->newIdentity(new class extends SessionIdentityManager {
            private array $records = [];

            public function setSessionIdentity(array $identity): void
            {
                $this->clearIdentityCache();
                $this->records[$this->getKey()] = $identity;
            }

            public function getSessionIdentity(): array
            {
                $key = $this->getKey();
                return $key === null ? [] : ($this->records[$key] ?? []);
            }
        });
        $this->anonymousTokens();
        self::assertTrue($this->login()['loggedIn']);
        self::assertSame(['userId' => 42], $this->identity->getSessionIdentity());
    }

    private function configure(bool $stateless = false, bool $fallback = true): void
    {
        foreach (['config', 'session', 'request'] as $service) {
            $this->di->remove($service);
        }
        $this->di->setShared('config', new Config(['identity' => [
            'stateless' => $stateless, 'sessionFallback' => $fallback,
            'token' => ['expiration' => time() + 3600],
            'refreshToken' => ['expiration' => time() + 7200],
        ]]));
        $this->di->setShared('session', $this->session);
        $this->di->setShared('request', new Request());
        $this->identity = $this->newIdentity();
    }

    private function newIdentity(?SessionIdentityManager $identity = null): SessionIdentityManager
    {
        $identity ??= new SessionIdentityManager();
        $identity->users = $this->users;
        $identity->setDI($this->di);
        $this->di->remove('identity');
        $this->di->setShared('identity', $identity);
        return $identity;
    }

    private function anonymousTokens(): array
    {
        $id = $this->session->getId();
        self::assertFalse($this->identity->isLoggedIn());
        $tokens = $this->identity->getJwt();
        self::assertSame($id, $this->session->getId());
        $_REQUEST = ['jwt' => $tokens['jwt']];
        $this->identity->setClaim([]);
        return $tokens;
    }

    private function login(): array
    {
        return $this->identity->login(['email' => 'synthetic@example.test', 'password' => 'Synthetic password!']);
    }

    private function openSession(string $id): SessionManager
    {
        $session = new SessionManager();
        $session->setAdapter(new Stream(['savePath' => $this->directory]));
        $session->setId($id);
        self::assertTrue($session->start());
        return $session;
    }

    private function request(string $id, array $params = []): void
    {
        session_write_close();
        $_SESSION = $_GET = $_POST = [];
        $_REQUEST = $params;
        $this->session = $this->openSession($id);
        $this->di->remove('session');
        $this->di->remove('request');
        $this->di->setShared('session', $this->session);
        $this->di->setShared('request', new Request());
        $this->identity = $this->newIdentity();
    }
}
