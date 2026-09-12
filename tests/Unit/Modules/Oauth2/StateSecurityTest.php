<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Modules\Oauth2;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Phalcon\Di\Di;
use PhalconKit\Config\Config;
use PhalconKit\Exception\HttpException;
use PhalconKit\Modules\Oauth2\Controllers\ClientController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StateSecurityTest extends TestCase
{
    private array $request;
    private ?\Phalcon\Di\DiInterface $previousDi;
    private object $session;
    private array $history = [];

    protected function setUp(): void
    {
        $this->previousDi = Di::getDefault();
        $this->request = $_REQUEST;
        $_REQUEST = [];
        $this->session = new class {
            public array $data = [];
            public function set(string $key, mixed $value): void
            {
                $this->data[$key] = $value;
            }
            public function get(string $key): mixed
            {
                return $this->data[$key] ?? null;
            }
            public function remove(string $key): void
            {
                unset($this->data[$key]);
            }
        };
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->request;
        Di::reset();
        if ($this->previousDi !== null) {
            Di::setDefault($this->previousDi);
        }
    }

    public function testCallbackConsumesStateAndRestoresPkceAcrossRequests(): void
    {
        $start = $this->controller();
        $start->authorizationUrlAction('email');
        $record = $this->session->get($start->sessionKey);
        self::assertGreaterThan(time(), $record['expiresAt']);
        self::assertNotEmpty($record['pkceCode']);

        $_REQUEST = ['state' => $record['state'], 'code' => 'synthetic-code'];
        $callback = $this->controller();
        self::assertTrue($callback->validateState());
        self::assertNull($this->session->get($callback->sessionKey));
        self::assertSame('synthetic-access', $callback->getAccessToken()->getToken());
        self::assertCount(1, $this->history);
        parse_str((string)$this->history[0]['request']->getBody(), $body);
        self::assertSame($record['pkceCode'], $body['code_verifier']);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $this->controller()->getAccessToken();
    }

    public function testDirectCodeExchangeValidatesStateAndCannotBeRepeated(): void
    {
        $controller = $this->controller();
        $controller->authorizationUrlAction('email');
        $_REQUEST = ['state' => $this->session->get($controller->sessionKey)['state'], 'code' => 'synthetic-code'];
        self::assertSame('synthetic-access', $controller->getAccessToken()->getToken());
        $this->expectException(HttpException::class);
        $controller->getAccessToken();
    }

    public static function invalidStates(): array
    {
        return array_map(static fn (string $case): array => [$case], ['missing', 'wrong', 'array', 'legacy', 'expired', 'provider', 'html']);
    }

    #[DataProvider('invalidStates')]
    public function testInvalidStateNeverExchangesCode(string $case): void
    {
        $controller = $this->controller();
        $controller->authorizationUrlAction('email');
        $record = $this->session->get($controller->sessionKey);
        $_REQUEST = ['state' => $record['state'], 'code' => 'synthetic-code'];
        switch ($case) {
            case 'missing':
                unset($_REQUEST['state']);
                break;
            case 'wrong':
                $_REQUEST['state'] = 'wrong';
                break;
            case 'array':
                $_REQUEST['state'] = [$record['state']];
                break;
            case 'legacy':
                $this->session->set($controller->sessionKey, $record['state']);
                break;
            case 'expired':
                $record['expiresAt'] = time() - 1;
                $this->session->set($controller->sessionKey, $record);
                break;
            case 'provider':
                $record['provider'] = 'google';
                $this->session->set($controller->sessionKey, $record);
                break;
            case 'html':
                $_REQUEST['state'] = '<b>' . $record['state'] . '</b>';
                break;
        }
        try {
            $controller->getAccessToken();
            self::fail('Invalid state must fail before provider exchange.');
        }
        catch (HttpException $exception) {
            self::assertSame(401, $exception->getCode());
            self::assertSame('Invalid OAuth2 authorization.', $exception->getMessage());
        }
        self::assertSame([], $this->history);
    }

    public function testValidStateCannotBeValidatedTwice(): void
    {
        $controller = $this->controller();
        $controller->authorizationUrlAction('email');
        $state = $this->session->get($controller->sessionKey)['state'];
        self::assertTrue($controller->validateState($state));
        self::assertFalse($controller->validateState($state));
    }

    public function testFailedProviderExchangeCannotReuseState(): void
    {
        $controller = $this->controller(new Response(400, ['Content-Type' => 'application/json'], '{"error":"invalid_grant"}'));
        $controller->authorizationUrlAction('email');
        $_REQUEST = ['state' => $this->session->get($controller->sessionKey)['state'], 'code' => 'synthetic-code'];
        try {
            $controller->getAccessToken();
            self::fail('The synthetic provider must reject this exchange.');
        }
        catch (IdentityProviderException) {
            self::assertCount(1, $this->history);
            self::assertNull($this->session->get($controller->sessionKey));
        }
        try {
            $controller->getAccessToken();
            self::fail('State must already be consumed even though exchange failed.');
        }
        catch (HttpException $exception) {
            self::assertSame(401, $exception->getCode());
            self::assertCount(1, $this->history);
        }
    }

    public static function invalidCodes(): array
    {
        return ['missing' => [null], 'empty' => [''], 'array' => [['synthetic-code']]];
    }

    #[DataProvider('invalidCodes')]
    public function testInvalidCodeConsumesStateWithoutProviderExchange(mixed $code): void
    {
        $controller = $this->controller();
        $controller->authorizationUrlAction('email');
        $_REQUEST = ['state' => $this->session->get($controller->sessionKey)['state'], 'code' => $code];
        try {
            $controller->getAccessToken();
            self::fail('Invalid code must fail before provider exchange.');
        }
        catch (HttpException $exception) {
            self::assertSame(401, $exception->getCode());
        }
        self::assertNull($this->session->get($controller->sessionKey));
        self::assertSame([], $this->history);
    }

    private function controller(?Response $tokenResponse = null): ClientController
    {
        $handler = HandlerStack::create(new MockHandler([
            $tokenResponse ?? new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"synthetic-access","token_type":"bearer","expires_in":3600}'),
        ]));
        $handler->push(Middleware::history($this->history));
        $provider = new GenericProvider([
            'clientId' => 'synthetic-client', 'clientSecret' => 'synthetic-secret',
            'redirectUri' => 'https://app.example.test/oauth2/callback',
            'urlAuthorize' => 'https://provider.example.test/authorize',
            'urlAccessToken' => 'https://provider.example.test/token',
            'urlResourceOwnerDetails' => 'https://provider.example.test/user',
            'pkceMethod' => 'S256',
        ], ['httpClient' => new Client(['handler' => $handler])]);
        $di = new \PhalconKit\Di\Di();
        $di->setShared('config', new Config(['oauth2' => ['stateLifetime' => 600]]));
        $di->setShared('oauth2Provider', $provider);
        $di->setShared('session', $this->session);
        $di->setShared('url', new \Phalcon\Mvc\Url());
        $response = new \Phalcon\Http\Response();
        $response->setDI($di);
        $di->setShared('response', $response);
        $di->setShared('request', new \PhalconKit\Http\Request());
        $controller = new class extends ClientController {
            public function initialize(): void
            {
            }
        };
        $controller->setDI($di);
        return $controller;
    }
}
