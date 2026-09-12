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
use Phalcon\Encryption\Security\JWT\Builder;
use Phalcon\Filter\FilterFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhalconKit\Config\Config;
use PhalconKit\Encryption\Security;
use PhalconKit\Exception\HttpException;
use PhalconKit\Http\Request;
use PhalconKit\Identity\Manager;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Provider\Jwt\Jwt;
use PhalconKit\Tests\Unit\Identity\Fixtures\IdentityUserModelDouble;

/**
 * Exercise the checkout's identity consumers with real Phalcon JWT validation.
 * Only session persistence and user records are replaced with isolated doubles.
 */
class JwtValidationTest extends TestCase
{
    private array $server;
    private array $request;
    private ?\Phalcon\Di\DiInterface $previousDi;
    private Manager $identity;
    private Jwt $jwt;
    private object $session;
    private string $passphrase;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->request = $_REQUEST;
        $this->previousDi = Di::getDefault();
        $_SERVER = ['HTTP_HOST' => 'jwt-validation.test', 'HTTPS' => 'on'];
        $_REQUEST = [];
        $this->passphrase = 'Synthetic!A9-' . bin2hex(random_bytes(64));
        IdentityUserModelDouble::reset();
        foreach ([42, 7] as $id) {
            $user = $this->createStub(UserInterface::class);
            $user->method('getId')->willReturn($id);
            IdentityUserModelDouble::$findFirstWithById[$id] = $user;
        }
        $this->configureIdentity();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_REQUEST = $this->request;
        IdentityUserModelDouble::reset();
        Di::reset();
        if ($this->previousDi !== null) {
            Di::setDefault($this->previousDi);
        }
    }

    public function testLoadsIdentityFromThisCheckout(): void
    {
        $this->assertSame(realpath(__DIR__ . '/../../../src/Identity/Manager.php'), (new \ReflectionClass($this->identity))->getFileName());
        $this->assertSame(realpath(__DIR__ . '/../../../src/Provider/Jwt/Jwt.php'), (new \ReflectionClass(Jwt::class))->getFileName());
    }

    #[DataProvider('storageModes')]
    public function testValidAccessAuthenticatesAndValidRefreshPreservesImpersonation(bool $stateless): void
    {
        $this->configureIdentity($stateless);
        $_REQUEST['jwt'] = $this->token(stateless: $stateless);

        $this->assertTrue($this->identity->isLoggedIn());
        $this->assertSame(42, $this->identity->getUserId());
        $this->assertSame(7, $this->identity->getUserAsId());

        // Refresh must inspect the refresh credential even with a cached access claim.
        $_REQUEST['refreshToken'] = $this->token(refresh: true, stateless: $stateless);
        $tokens = $this->identity->getJwt(true);

        $this->assertTrue($tokens['refreshed']);
        $this->assertNotSame('existing-key', $this->identity->getKey());
        $this->assertSame(['userId' => 42, 'asUserId' => 7], $this->identity->getSessionIdentity());
        $this->assertSame(2, $this->jwt->builds);
        foreach (['jwt' => false, 'refreshToken' => true] as $field => $refresh) {
            $this->assertSame($this->identity->claim, $this->identity->getClaimFromToken($tokens[$field], $this->identity->getSessionKey($refresh)));
        }
        if ($stateless) {
            $this->assertSame([], $this->session->calls);
        } else {
            $this->assertArrayNotHasKey('existing-key', $this->session->data);
            $this->assertSame(['userId' => 42, 'asUserId' => 7], $this->session->data[$this->identity->getKey()]);
        }
    }

    /** @return array<string, array{bool}> */
    public static function storageModes(): array
    {
        return ['session' => [false], 'stateless' => [true]];
    }

    #[DataProvider('invalidTokens')]
    public function testInvalidCredentialsStopBeforeIdentityReadsOrTokenIssuance(string $case, bool $refresh, bool $stateless): void
    {
        $this->configureIdentity($stateless, fallback: true);
        $_REQUEST[$refresh ? 'refreshToken' : 'jwt'] = $this->invalidToken($case, $refresh, $stateless);
        $before = $this->session->data;

        $this->assertUnauthorized(function () use ($refresh): void {
            if ($refresh) {
                $this->identity->getJwt(true);
            } else {
                $this->identity->isLoggedIn();
            }
        });

        $this->assertSame([], $this->identity->claim);
        $this->assertSame([], IdentityUserModelDouble::$findFirstWithCalls);
        $this->assertSame([], $this->session->calls);
        $this->assertSame($before, $this->session->data);
        $this->assertSame(0, $this->jwt->builds);
    }

    /** @return iterable<string, array{string, bool, bool}> */
    public static function invalidTokens(): iterable
    {
        foreach (['expired', 'signature', 'substitution', 'id', 'issuer', 'audience', 'not-before', 'issued-at', 'malformed', 'header', 'claims', 'date', 'date-type', 'subject-json', 'subject-type', 'zero'] as $case) {
            foreach ([false, true] as $refresh) {
                foreach ([false, true] as $stateless) {
                    yield $case . ($refresh ? ' refresh' : ' access') . ($stateless ? ' stateless' : ' session') => [$case, $refresh, $stateless];
                }
            }
        }
    }

    #[DataProvider('storageModes')]
    public function testFailedRefreshPreservesPreviouslyResolvedIdentity(bool $stateless): void
    {
        $this->configureIdentity($stateless, fallback: true);
        $_REQUEST['jwt'] = $this->token(stateless: $stateless);
        $claim = $this->identity->getClaim();
        $_REQUEST['refreshToken'] = $this->invalidToken('expired', true, $stateless);
        $before = $this->session->data;
        $this->session->calls = [];

        $this->assertUnauthorized(fn () => $this->identity->getJwt(true));

        $this->assertSame($claim, $this->identity->claim);
        $this->assertSame($before, $this->session->data);
        $this->assertSame([], $this->session->calls);
        $this->assertSame(0, $this->jwt->builds);
    }

    public function testInvalidBearerCannotUseAuthenticatedSessionFallback(): void
    {
        $this->configureIdentity(fallback: true);
        $_SERVER['HTTP_X_AUTHORIZATION'] = 'Bearer ' . $this->invalidToken('signature', false, false);

        $this->assertUnauthorized(fn () => $this->identity->isLoggedIn());
        $this->assertSame([], $this->session->calls);
    }

    #[DataProvider('malformedCredentials')]
    public function testMalformedCredentialSourcesCannotFallBack(string $source, mixed $credential): void
    {
        $this->configureIdentity(fallback: true);
        if ($source === 'header') {
            $_SERVER['HTTP_X_AUTHORIZATION'] = $credential;
        } else {
            $_REQUEST[$source] = $credential;
        }

        $this->assertUnauthorized(fn () => $this->identity->getJwt($source === 'refreshToken'));
        $this->assertSame([], $this->session->calls);
        $this->assertSame(0, $this->jwt->builds);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function malformedCredentials(): iterable
    {
        foreach (['jwt', 'refreshToken'] as $source) {
            yield $source . ' array' => [$source, ['not-a-token']];
            yield $source . ' empty array' => [$source, []];
            yield $source . ' integer' => [$source, 0];
        }
        yield 'missing bearer value' => ['header', 'Bearer'];
        yield 'extra bearer value' => ['header', 'Bearer bad extra'];
    }

    public function testBearerWhitespaceAndUnsupportedSchemesRemainCompatible(): void
    {
        $_SERVER['HTTP_X_AUTHORIZATION'] = "  bEaReR\t  " . $this->token() . '  ';
        $this->assertSame(42, $this->identity->getUserId());
        $this->configureIdentity(fallback: true);
        $_SERVER['HTTP_X_AUTHORIZATION'] = 'Basic unsupported';
        $this->assertFalse($this->identity->isLoggedIn());
        $this->assertSame([], $this->session->calls);
    }

    public function testCredentialSanitizingCannotTurnMalformedInputIntoValidToken(): void
    {
        $_REQUEST['jwt'] = '<b>' . $this->token() . '</b>';
        $this->assertUnauthorized(fn () => $this->identity->isLoggedIn());
        $this->assertSame([], $this->session->calls);
    }

    public function testRefreshTokenCannotAuthenticateAsBearer(): void
    {
        $_SERVER['HTTP_X_AUTHORIZATION'] = 'Bearer ' . $this->token(refresh: true);
        $this->assertUnauthorized(fn () => $this->identity->isLoggedIn());
        $this->assertSame([], $this->session->calls);
    }

    #[DataProvider('storageModes')]
    public function testJsonRefreshRejectsInvalidCredentialBeforeCustomPersistenceHooks(bool $stateless): void
    {
        $this->configureIdentity($stateless);
        $di = $this->identity->getDI();
        $token = $this->invalidToken('expired', true, $stateless);
        $request = new class ($token) extends Request {
            public function __construct(private string $token)
            {
            }

            public function getJsonRawBody(bool $associative = false): \stdClass|array|bool
            {
                return (object)['refreshToken' => $this->token];
            }
        };
        $di->setShared('request', $request);
        $identity = new class extends Manager {
            public array $persistenceCalls = [];

            public function getSessionIdentity(): array
            {
                $this->persistenceCalls[] = 'read';
                return ['userId' => 42, 'asUserId' => 7];
            }

            public function setSessionIdentity(array $identity): void
            {
                $this->persistenceCalls[] = 'write';
            }

            public function removeSessionIdentity(): void
            {
                $this->persistenceCalls[] = 'remove';
            }
        };
        $identity->setDI($di);

        $this->assertUnauthorized(fn () => $identity->getJwt(true));
        $this->assertSame([], $identity->persistenceCalls);
        $this->assertSame([], $identity->claim);
        $this->assertSame(0, $this->jwt->builds);
    }

    public function testNoTokenStillSupportsAnonymousIssuanceAndSessionFallback(): void
    {
        $this->assertFalse($this->identity->isLoggedIn());
        $this->assertSame([], $this->identity->getClaim());
        $tokens = $this->identity->getJwt();
        $this->assertFalse($tokens['refreshed']);
        $this->assertNotEmpty($this->identity->getClaimFromToken($tokens['jwt'], $this->identity->getSessionKey())['key']);

        $this->configureIdentity(fallback: true);
        $this->assertSame(42, $this->identity->getUserId());

        $this->configureIdentity(stateless: true, fallback: true);
        $this->assertFalse($this->identity->isLoggedIn());
        $this->assertSame([], $this->session->calls);
    }

    public function testRefreshWithoutRefreshCredentialKeepsLegacySourcePrecedence(): void
    {
        // The enforcement fix does not introduce a refresh-token-only policy.
        $_REQUEST['jwt'] = $this->token();
        $tokens = $this->identity->getJwt(true);
        $this->assertTrue($tokens['refreshed']);
        $this->assertSame(['userId' => 42, 'asUserId' => 7], $this->identity->getSessionIdentity());

        $_REQUEST = ['jwt' => '', 'refreshToken' => null];
        $this->configureIdentity(fallback: true);
        $tokens = $this->identity->getJwt(true);
        $this->assertTrue($tokens['refreshed']);
        $this->assertSame(['userId' => 42, 'asUserId' => 7], $this->identity->getSessionIdentity());

        $this->configureIdentity();
        $tokens = $this->identity->getJwt(true);
        $this->assertTrue($tokens['refreshed']);
        $this->assertSame([], $this->identity->getSessionIdentity());
    }

    #[DataProvider('storageModes')]
    public function testValidTokenSupportsLogoutAndLeavingImpersonation(bool $stateless): void
    {
        $this->configureIdentity($stateless);
        $_REQUEST['jwt'] = $this->token(stateless: $stateless);
        $this->assertTrue($this->identity->isLoggedInAs());
        if (!$stateless) {
            $result = $this->identity->logoutAs();
            $this->assertTrue($result['loggedIn']);
            $this->assertFalse($result['loggedInAs']);
            $this->assertSame(['userId' => 7], $this->identity->getSessionIdentity());
        }
        $result = $this->identity->logout();
        $this->assertFalse($result['loggedIn']);
        $this->assertFalse($result['loggedInAs']);
        $this->assertSame([], $this->identity->getSessionIdentity());
        if ($stateless) {
            $claim = $this->identity->getClaimFromToken($result['jwt'], $this->identity->getSessionKey());
            $this->assertSame(['key'], array_keys($claim));
            $this->assertSame([], $this->session->calls);
        }
    }

    private function configureIdentity(bool $stateless = false, bool $fallback = false): void
    {
        $this->session = new class {
            use \PhalconKit\Tests\Unit\Identity\Fixtures\SessionLifecycleDouble;

            public array $data = [
                'existing-key' => ['userId' => 42, 'asUserId' => 7],
                Manager::SESSION_KEY => ['key' => 'existing-key'],
            ];
            public array $calls = [];

            public function get(string $key): mixed
            {
                $this->calls[] = ['get', $key];
                return $this->data[$key] ?? null;
            }

            public function has(string $key): bool
            {
                $this->calls[] = ['has', $key];
                return isset($this->data[$key]);
            }

            public function set(string $key, mixed $value): void
            {
                $this->calls[] = ['set', $key];
                $this->data[$key] = $value;
            }

            public function remove(string $key): void
            {
                $this->calls[] = ['remove', $key];
                unset($this->data[$key]);
            }
        };
        $this->jwt = new class (['passphrase' => $this->passphrase]) extends Jwt {
            public int $builds = 0;

            public function builder(array $options = []): Builder
            {
                $this->builds++;
                return parent::builder($options);
            }
        };
        $di = new Di();
        $di->setShared('request', new Request());
        $di->setShared('filter', new FilterFactory()->newInstance());
        $di->setShared('config', new Config(['identity' => [
            'stateless' => $stateless,
            'sessionFallback' => $fallback,
            'token' => ['expiration' => time() + 3600],
            'refreshToken' => ['expiration' => time() + 604800],
        ]]));
        $di->setShared('jwt', $this->jwt);
        $di->setShared('security', new Security());
        $di->setShared('session', $this->session);
        $di->setShared('models', new class {
            public function getUser(): string
            {
                return IdentityUserModelDouble::class;
            }
        });
        $this->identity = new Manager();
        $this->identity->setDI($di);
        $di->setShared('identity', $this->identity);
    }

    private function invalidToken(string $case, bool $refresh, bool $stateless): string
    {
        $overrides = match ($case) {
            'expired' => ['exp' => time() - 864000],
            'substitution' => ['jti' => $this->identity->getSessionKey(!$refresh)],
            'id' => ['jti' => 'wrong-id'],
            'issuer' => ['iss' => 'https://wrong-issuer.test'],
            'audience' => ['aud' => ['https://wrong-audience.test']],
            'not-before' => ['nbf' => time() + 86400],
            'issued-at' => ['iat' => time() + 86400],
            'date' => ['exp' => 'not-a-date'],
            'date-type' => ['nbf' => ['invalid']],
            'subject-json' => ['sub' => '{not-json'],
            'subject-type' => ['sub' => ['userId' => 42]],
            default => [],
        };
        return match ($case) {
            'malformed' => 'not-a-jwt',
            'zero' => '0',
            'header' => 'bm90LWpzb24.e30.c2lnbmF0dXJl',
            'claims' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.bm90LWpzb24.c2lnbmF0dXJl',
            default => $this->token($refresh, $stateless, $overrides, $case === 'signature' ? 'Wrong!B8-' . bin2hex(random_bytes(64)) : null),
        };
    }

    /** Sign synthetic payloads directly so expired/future claims are not blocked by the builder. */
    private function token(bool $refresh = false, bool $stateless = false, array $overrides = [], ?string $passphrase = null): string
    {
        $subject = ['key' => 'existing-key'];
        if ($stateless) {
            $subject += ['userId' => 42, 'asUserId' => 7];
        }
        $payload = array_replace([
            'iss' => 'https://jwt-validation.test',
            'aud' => ['https://jwt-validation.test'],
            'jti' => $this->identity->getSessionKey($refresh),
            'iat' => time() - 1728000,
            'nbf' => time() - 1728000,
            'exp' => time() + 3600,
            'sub' => json_encode($subject, JSON_THROW_ON_ERROR),
        ], $overrides);
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $input = $encode('{"typ":"JWT","alg":"HS512"}') . '.' . $encode(json_encode($payload, JSON_THROW_ON_ERROR));
        return $input . '.' . $encode(hash_hmac('sha512', $input, $passphrase ?? $this->passphrase, true));
    }

    private function assertUnauthorized(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Invalid credentials were accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(401, $exception->getCode());
            $this->assertSame('Invalid authentication token.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }
}
