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

namespace PhalconKit\Tests\Unit\Provider;

use Aws\Sdk;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Google;
use OpenAI\Contracts\ClientContract;
use Phalcon\Annotations\Adapter\Memory as AnnotationsMemory;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Di\Di;
use Phalcon\Encryption\Crypt;
use Phalcon\Events\Manager;
use Phalcon\Filter\Sanitize\Upper;
use Phalcon\Flash\Direct;
use Phalcon\Http\Response\Cookies;
use Phalcon\Mvc\Model\MetaData\Memory as MetadataMemory;
use Phalcon\Mvc\Model\MetaData\Stream as MetadataStream;
use Phalcon\Session\Adapter\Noop as SessionNoop;
use Phalcon\Session\Adapter\Redis as SessionRedis;
use Phalcon\Session\Adapter\Stream as SessionStream;
use Phalcon\Session\Manager as SessionManager;
use PhalconKit\Acl\Acl;
use PhalconKit\Assets\Manager as AssetsManager;
use PhalconKit\Bootstrap;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Cli\Dispatcher as CliDispatcher;
use PhalconKit\Cli\Router as CliRouter;
use PhalconKit\Html\Escaper;
use PhalconKit\Http\Request;
use PhalconKit\Identity\Manager as IdentityManager;
use PhalconKit\Mvc\Dispatcher as MvcDispatcher;
use PhalconKit\Mvc\View;
use PhalconKit\Provider\Acl\ServiceProvider as AclProvider;
use PhalconKit\Provider\Annotations\ServiceProvider as AnnotationsProvider;
use PhalconKit\Provider\Assets\ServiceProvider as AssetsProvider;
use PhalconKit\Provider\Aws\ServiceProvider as AwsProvider;
use PhalconKit\Provider\Cookies\ServiceProvider as CookiesProvider;
use PhalconKit\Provider\Crypt\ServiceProvider as CryptProvider;
use PhalconKit\Provider\Database\ServiceProvider as DatabaseProvider;
use PhalconKit\Provider\Debug\ServiceProvider as DebugProvider;
use PhalconKit\Provider\Dispatcher\ServiceProvider as DispatcherProvider;
use PhalconKit\Provider\Filter\ServiceProvider as FilterProvider;
use PhalconKit\Provider\Flash\ServiceProvider as FlashProvider;
use PhalconKit\Provider\Identity\ServiceProvider as IdentityProvider;
use PhalconKit\Provider\Imap\ServiceProvider as ImapProvider;
use PhalconKit\Provider\Jwt\Jwt;
use PhalconKit\Provider\Jwt\ServiceProvider as JwtProvider;
use PhalconKit\Provider\LoremIpsum\ServiceProvider as LoremIpsumProvider;
use PhalconKit\Provider\Mailer\ServiceProvider as MailerProvider;
use PhalconKit\Provider\ModelsMetadata\ServiceProvider as ModelsMetadataProvider;
use PhalconKit\Provider\OCR\ServiceProvider as OCRProvider;
use PhalconKit\Provider\Oauth2Client\ServiceProvider as Oauth2ClientProvider;
use PhalconKit\Provider\Oauth2Facebook\ServiceProvider as Oauth2FacebookProvider;
use PhalconKit\Provider\Oauth2Google\ServiceProvider as Oauth2GoogleProvider;
use PhalconKit\Provider\OpenAi\ServiceProvider as OpenAiProvider;
use PhalconKit\Provider\ReCaptcha\ServiceProvider as ReCaptchaProvider;
use PhalconKit\Provider\Redis\ServiceProvider as RedisProvider;
use PhalconKit\Provider\Router\ServiceProvider as RouterProvider;
use PhalconKit\Provider\Session\ServiceProvider as SessionProvider;
use PhalconKit\Provider\Swoole\ServiceProvider as SwooleProvider;
use PhalconKit\Provider\Volt\ServiceProvider as VoltProvider;
use PhalconKit\Support\Debug;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Ws\Dispatcher as WsDispatcher;
use PhalconKit\Ws\Router as WsRouter;
use PhpImap\Mailbox;
use ReCaptcha\ReCaptcha;
use Redis;
use thiagoalessio\TesseractOCR\TesseractOCR;

class AdditionalServiceProvidersTest extends AbstractUnit
{
    public function testAclProviderBuildsAclFromConfiguredPermissions(): void
    {
        $di = $this->createDi([
            'permissions' => [
                'features' => [
                    'viewFoos' => [
                        'components' => [
                            'FooController' => ['index'],
                        ],
                    ],
                ],
                'roles' => [
                    'user' => [
                        'features' => ['viewFoos'],
                    ],
                ],
            ],
        ]);
        (new AclProvider($di))->register($di);

        $aclService = $di->get('acl');
        $acl = $aclService->get();

        $this->assertInstanceOf(Acl::class, $aclService);
        $this->assertTrue($acl->isAllowed('user', 'FooController', 'index'));
        $this->assertFalse($acl->isAllowed('user', 'FooController', 'delete'));
    }

    public function testAnnotationsProviderRegistersConfiguredAdapter(): void
    {
        $di = $this->createDi();
        (new AnnotationsProvider($di))->register($di);

        $this->assertInstanceOf(AnnotationsMemory::class, $di->get('annotations'));
    }

    public function testAnnotationsProviderDefaultsToMemoryAdapterWithoutWarnings(): void
    {
        $di = $this->createBareDi([
            'annotations' => [
                'driver' => 'memory',
                'drivers' => [
                    'memory' => [],
                ],
            ],
        ]);
        (new AnnotationsProvider($di))->register($di);

        $this->withoutPhpWarnings(function () use ($di): void {
            $this->assertInstanceOf(AnnotationsMemory::class, $di->get('annotations'));
        });
    }

    public function testAssetsProviderUsesEscaperService(): void
    {
        $di = $this->createDi();
        $di->set('escaper', new Escaper());
        (new AssetsProvider($di))->register($di);

        $this->assertInstanceOf(AssetsManager::class, $di->get('assets'));
    }

    public function testAwsProviderRegistersSdk(): void
    {
        $di = $this->createDi([
            'aws' => [
                'region' => 'us-east-1',
                'version' => 'latest',
                'credentials' => [
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
            ],
        ]);
        (new AwsProvider($di))->register($di);

        $this->assertInstanceOf(Sdk::class, $di->get('aws'));
    }

    public function testCookiesProviderAppliesConfiguredDefaults(): void
    {
        $di = $this->createDi([
            'cookies' => [
                'useEncryption' => false,
                'signKey' => 'unit-test-sign-key',
            ],
        ]);
        (new CookiesProvider($di))->register($di);

        $this->assertInstanceOf(Cookies::class, $di->get('cookies'));
        $this->assertSame($di->get('cookies'), $di->get('cookies'));
    }

    public function testCryptProviderRegistersValidCryptService(): void
    {
        $key = str_repeat('k', 32);
        $di = $this->createDi([
            'crypt' => [
                'cipher' => 'aes-256-cbc',
                'key' => $key,
                'useSigning' => true,
                'hashAlgorithm' => 'sha256',
            ],
        ]);
        (new CryptProvider($di))->register($di);

        $crypt = $di->get('crypt');

        $this->assertInstanceOf(Crypt::class, $crypt);
        $this->assertSame('aes-256-cbc', $crypt->getCipher());
        $this->assertSame($key, $crypt->getKey());
        $this->assertSame('sha256', $crypt->getHashAlgorithm());
    }

    public function testCryptProviderRejectsShortKeys(): void
    {
        $di = $this->createDi([
            'crypt' => [
                'key' => 'short',
                'useSigning' => false,
            ],
        ]);
        (new CryptProvider($di))->register($di);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid encryption key');

        $di->get('crypt');
    }

    public function testCryptProviderRejectsSigningForAeadCiphers(): void
    {
        $di = $this->createDi([
            'crypt' => [
                'cipher' => 'aes-256-gcm',
                'key' => str_repeat('k', 32),
                'useSigning' => true,
            ],
        ]);
        (new CryptProvider($di))->register($di);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AEAD');

        $di->get('crypt');
    }

    public function testCryptProviderAllowsRuntimeCipherAndSigningOverrides(): void
    {
        $di = $this->createDi([
            'crypt' => [
                'cipher' => 'aes-256-gcm',
                'key' => str_repeat('k', 32),
                'useSigning' => false,
                'hashAlgorithm' => 'sha512',
            ],
        ]);
        (new CryptProvider($di))->register($di);

        $crypt = $di->get('crypt', ['aes-256-cbc', true]);

        $this->assertSame('aes-256-cbc', $crypt->getCipher());
        $this->assertSame('sha512', $crypt->getHashAlgorithm());
    }

    public function testCryptProviderRejectsSigningForStreamCiphers(): void
    {
        $di = $this->createDi([
            'crypt' => [
                'cipher' => 'aes-256-ctr',
                'key' => str_repeat('k', 32),
                'useSigning' => true,
            ],
        ]);
        (new CryptProvider($di))->register($di);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('stream mode');

        $di->get('crypt');
    }

    public function testCryptProviderRejectsUnsupportedCipher(): void
    {
        $di = $this->createDi([
            'crypt' => [
                'cipher' => 'not-a-real-cipher',
                'key' => str_repeat('k', 32),
                'useSigning' => false,
            ],
        ]);
        (new CryptProvider($di))->register($di);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid cipher "not-a-real-cipher"');

        $di->get('crypt');
    }

    public function testDatabaseProviderRejectsNonArrayDriverOptions(): void
    {
        $di = $this->createDi([
            'database' => [
                'default' => 'broken',
                'drivers' => [
                    'broken' => false,
                ],
            ],
        ]);
        $di->set('eventsManager', new Manager());
        (new DatabaseProvider($di))->register($di);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an array');

        $di->get('db');
    }

    public function testDatabaseProviderMergesExtendedDriverOptionsAndRemovesControlKeys(): void
    {
        $fakePdoAdapter = $this->createFakePdoAdapterClass();
        $di = $this->createDi([
            'database' => [
                'default' => 'unit',
                'drivers' => [
                    'base' => [
                        'adapter' => $fakePdoAdapter,
                        'host' => 'base-host',
                        'username' => 'base-user',
                        'password' => 'base-pass',
                    ],
                    'unit' => [
                        'extends' => 'base',
                        'enable' => true,
                        'dbname' => 'unit-db',
                        'password' => null,
                    ],
                ],
            ],
        ]);
        $di->set('eventsManager', new Manager());
        (new DatabaseProvider($di))->register($di);

        $db = $di->get('db');

        $this->assertInstanceOf($fakePdoAdapter, $db);
        $this->assertSame('base-host', $db->descriptor['host']);
        $this->assertSame('base-user', $db->descriptor['username']);
        $this->assertSame('unit-db', $db->descriptor['dbname']);
        $this->assertSame('base-pass', $db->descriptor['password']);
        $this->assertArrayNotHasKey('extends', $db->descriptor);
        $this->assertArrayNotHasKey('enable', $db->descriptor);
        $this->assertSame($di->get('eventsManager'), $db->getEventsManager());
    }

    public function testDatabaseProviderFallsBackToDefaultDriverAndBuildsDialect(): void
    {
        $fakePdoAdapter = $this->createFakePdoAdapterClass();
        $fakeDialect = $this->createFakeDialectClass();
        $di = $this->createDi([
            'database' => [
                'default' => 'unit',
                'drivers' => [
                    'unit' => [
                        'adapter' => $fakePdoAdapter,
                        'dialectClass' => $fakeDialect,
                        'host' => 'unit-host',
                    ],
                ],
            ],
        ]);
        $di->set('eventsManager', new Manager());
        $provider = new class ($di) extends DatabaseProvider {
            protected ?string $driverName = 'missing';
        };
        $provider->register($di);

        $db = $di->get('db');

        $this->assertInstanceOf($fakePdoAdapter, $db);
        $this->assertSame('unit-host', $db->descriptor['host']);
        $this->assertInstanceOf($fakeDialect, $db->descriptor['dialectClass']);
    }

    public function testDebugProviderRegistersDebugServiceAndReportsCyclicGuard(): void
    {
        $di = $this->createDi([
            'app' => [
                'debug' => false,
            ],
            'debug' => [
                'enable' => false,
            ],
        ]);
        $di->set('bootstrap', $this->bootstrap);
        $provider = new DebugProvider($di);
        $provider->register($di);

        $this->assertSame(
            version_compare(PHP_VERSION, '8.0.0', '>=')
                && version_compare((new \Phalcon\Support\Version())->get(), '5.0.0', '<'),
            $provider->causeCyclicError()
        );
        $this->assertInstanceOf(Debug::class, $di->get('debug'));
    }

    public function testDebugProviderAppliesConfiguredUriWhenEnabled(): void
    {
        $di = $this->createDi([
            'app' => [
                'debug' => true,
            ],
            'debug' => [
                'enable' => true,
                'exceptions' => false,
                'lowSeverity' => false,
                'showFiles' => false,
                'showBackTrace' => false,
                'showFileFragment' => false,
                'blacklist' => [
                    'server' => ['HTTP_AUTHORIZATION'],
                ],
                'uri' => '/debug-assets/',
            ],
        ]);
        $this->bootstrap->mode = Bootstrap::MODE_MVC;
        $di->set('bootstrap', $this->bootstrap);
        (new DebugProvider($di))->register($di);

        $debug = $di->get('debug');
        $uri = new \ReflectionProperty(\Phalcon\Support\Debug::class, 'uri');

        $this->assertInstanceOf(Debug::class, $debug);
        $this->assertSame('/debug-assets/', $uri->getValue($debug));
    }

    public function testDebugProviderAllowsEnabledDebugWithoutConfiguredUri(): void
    {
        $di = $this->createBareDi([
            'app' => [
                'debug' => true,
            ],
            'debug' => [
                'enable' => true,
            ],
        ]);
        $this->bootstrap->mode = Bootstrap::MODE_MVC;
        $di->set('bootstrap', $this->bootstrap);
        (new DebugProvider($di))->register($di);

        try {
            $this->withoutPhpWarnings(function () use ($di): void {
                $this->assertInstanceOf(Debug::class, $di->get('debug'));
            });
        }
        finally {
            restore_exception_handler();
        }
    }

    public function testDispatcherProviderRegistersMvcDispatcherWithDefaultNamespace(): void
    {
        $di = $this->createDi([
            'router' => [
                'defaults' => [
                    'namespace' => 'Unit\\Controllers',
                ],
            ],
        ]);
        $di->set('bootstrap', $this->bootstrap);
        $di->set('eventsManager', new Manager());
        (new DispatcherProvider($di))->register($di);

        $dispatcher = $di->get('dispatcher');

        $this->assertInstanceOf(MvcDispatcher::class, $dispatcher);
        $this->assertSame('Unit\\Controllers', $dispatcher->getDefaultNamespace());
        $this->assertSame($di, $dispatcher->getDI());
        $this->assertGreaterThan(0, count($di->get('eventsManager')->getListeners('dispatch')));
    }

    public function testDispatcherProviderRegistersCliDispatcherForCliBootstrapMode(): void
    {
        $di = $this->createDi();
        $this->bootstrap->mode = Bootstrap::MODE_CLI;
        $di->set('bootstrap', $this->bootstrap);
        $di->set('eventsManager', new Manager());
        (new DispatcherProvider($di))->register($di);

        $dispatcher = $di->get('dispatcher');

        $this->assertInstanceOf(CliDispatcher::class, $dispatcher);
        $this->assertSame($di, $dispatcher->getDI());
    }

    public function testDispatcherProviderRegistersWsDispatcherForWsBootstrapMode(): void
    {
        $di = $this->createDi();
        $this->bootstrap->mode = Bootstrap::MODE_WS;
        $di->set('bootstrap', $this->bootstrap);
        $di->set('eventsManager', new Manager());
        (new DispatcherProvider($di))->register($di);

        $dispatcher = $di->get('dispatcher');

        $this->assertInstanceOf(WsDispatcher::class, $dispatcher);
        $this->assertSame($di, $dispatcher->getDI());
    }

    public function testFilterProviderRegistersConfiguredFilterServices(): void
    {
        $di = $this->createDi([
            'filters' => [
                'unit-upper' => Upper::class,
            ],
        ]);
        (new FilterProvider($di))->register($di);

        $filter = $di->get('filter');

        $this->assertSame('ADA', $filter->sanitize('ada', ['unit-upper']));
    }

    public function testFlashProviderRegistersDirectFlashService(): void
    {
        $di = $this->createDi();
        (new FlashProvider($di))->register($di);

        $flash = $di->get('flash');

        $this->assertInstanceOf(Direct::class, $flash);
        $this->assertSame($di, $flash->getDI());
    }

    public function testIdentityProviderRegistersManagerWithOptionsAndDi(): void
    {
        $di = $this->createDi([
            'identity' => [
                'sessionKey' => 'unit-identity',
            ],
        ]);
        (new IdentityProvider($di))->register($di);

        $identity = $di->get('identity');

        $this->assertInstanceOf(IdentityManager::class, $identity);
        $this->assertSame($di, $identity->getDI());
        $this->assertSame('unit-identity', $identity->getOption('sessionKey'));
    }

    public function testJwtProviderRegistersConfiguredJwtHelper(): void
    {
        $di = $this->createDi([
            'security' => [
                'jwt' => [
                    'issuer' => 'unit-issuer',
                    'audience' => 'unit-audience',
                    'passphrase' => 'unit-passphrase',
                ],
            ],
        ]);
        (new JwtProvider($di))->register($di);

        $jwt = $di->get('jwt');

        $this->assertInstanceOf(Jwt::class, $jwt);
        $this->assertSame('unit-issuer', $jwt->options['issuer']);
        $this->assertSame('unit-audience', $jwt->options['audience']);
    }

    public function testLoremIpsumProviderRegistersGenerator(): void
    {
        $di = $this->createDi();
        (new LoremIpsumProvider($di))->register($di);

        $this->assertInstanceOf(\joshtronic\LoremIpsum::class, $di->get('loremIpsum'));
    }

    public function testImapProviderUsesConfiguredOptionsAndRuntimeOverrides(): void
    {
        $di = $this->createDi([
            'imap' => [
                'path' => '{imap.example.test:993/imap/ssl}INBOX',
                'login' => 'config-user',
                'password' => 'config-pass',
                'attachmentsDir' => sys_get_temp_dir(),
                'serverEncoding' => 'ISO-8859-1',
                'trimImapPath' => false,
                'attachmentFilenameMode' => true,
            ],
        ]);
        (new ImapProvider($di))->register($di);

        $mailbox = $di->get('imap');

        $this->assertInstanceOf(Mailbox::class, $mailbox);
        $this->assertSame('config-user', $mailbox->getLogin());
        $this->assertSame(sys_get_temp_dir(), $mailbox->getAttachmentsDir());
        $this->assertSame('ISO-8859-1', $mailbox->getServerEncoding());
        $this->assertTrue($mailbox->getAttachmentFilenameMode());

        $overrideDi = $this->createDi();
        (new ImapProvider($overrideDi))->register($overrideDi);
        $overrideMailbox = $overrideDi->get('imap', [[
            'path' => '{imap.example.test:993/imap/ssl}Archive',
            'login' => 'runtime-user',
            'password' => 'runtime-pass',
        ]]);

        $this->assertSame('runtime-user', $overrideMailbox->getLogin());
    }

    public function testMailerProviderRegistersManagerWithEventsAndSmtpAuth(): void
    {
        $di = $this->createDi([
            'mailer' => [
                'driver' => 'smtp',
                'drivers' => [
                    'smtp' => [
                        'driver' => 'smtp',
                        'host' => 'localhost',
                        'port' => 25,
                    ],
                ],
                'defaults' => [
                    'charset' => 'utf-8',
                ],
            ],
        ]);
        $eventsManager = new Manager();
        $di->set('eventsManager', $eventsManager);
        (new MailerProvider($di))->register($di);

        $mailer = $di->get('mailer');

        $this->assertInstanceOf(\Phalcon\Incubator\Mailer\Manager::class, $mailer);
        $this->assertSame($di, $mailer->getDI());
        $this->assertSame($eventsManager, $mailer->getEventsManager());
        $this->assertTrue($mailer->getMailer()->SMTPAuth);
    }

    public function testModelsMetadataProviderRegistersMemoryAdapter(): void
    {
        $di = $this->createDi();
        $di->set('bootstrap', $this->bootstrap);
        (new ModelsMetadataProvider($di))->register($di);

        $this->assertInstanceOf(MetadataMemory::class, $di->get('modelsMetadata'));
    }

    public function testModelsMetadataProviderDefaultsToMemoryAdapterWithoutWarnings(): void
    {
        $di = $this->createBareDi([
            'metadata' => [
                'driverCli' => 'memory',
                'driver' => 'memory',
                'drivers' => [
                    'memory' => [],
                ],
            ],
        ]);
        $di->set('bootstrap', $this->bootstrap);
        (new ModelsMetadataProvider($di))->register($di);

        $this->withoutPhpWarnings(function () use ($di): void {
            $this->assertInstanceOf(MetadataMemory::class, $di->get('modelsMetadata'));
        });
    }

    public function testModelsMetadataProviderCanUseConfiguredStreamAdapter(): void
    {
        $di = $this->createDi([
            'metadata' => [
                'driverCli' => 'stream',
                'driver' => 'stream',
                'drivers' => [
                    'stream' => [
                        'adapter' => MetadataStream::class,
                        'metaDataDir' => sys_get_temp_dir(),
                    ],
                ],
                'default' => [
                    'lifetime' => 60,
                ],
            ],
        ]);
        $di->set('bootstrap', $this->bootstrap);
        (new ModelsMetadataProvider($di))->register($di);

        $this->assertInstanceOf(MetadataStream::class, $di->get('modelsMetadata'));
    }

    public function testModelsMetadataProviderCanUseCacheFactoryAdapter(): void
    {
        $fakeMetadataAdapter = $this->createFakeMetadataAdapterClass();
        $di = $this->createDi([
            'metadata' => [
                'driverCli' => 'unit',
                'driver' => 'unit',
                'drivers' => [
                    'unit' => [
                        'adapter' => $fakeMetadataAdapter,
                        'prefix' => 'unit-prefix',
                    ],
                ],
                'default' => [
                    'lifetime' => 60,
                ],
            ],
        ]);
        $di->set('bootstrap', $this->bootstrap);
        (new ModelsMetadataProvider($di))->register($di);

        $metadata = $di->get('modelsMetadata');

        $this->assertInstanceOf($fakeMetadataAdapter, $metadata);
        $this->assertSame(60, $metadata->options['lifetime']);
        $this->assertSame('unit-prefix', $metadata->options['prefix']);
    }

    public function testOcrProviderRegistersTesseractClient(): void
    {
        $di = $this->createDi();
        (new OCRProvider($di))->register($di);

        $this->assertInstanceOf(TesseractOCR::class, $di->get('ocr'));
    }

    public function testOauth2ClientProviderRegistersGenericProvider(): void
    {
        $di = $this->createDi([
            'oauth2' => [
                'client' => [
                    'clientId' => 'client-id',
                    'clientSecret' => 'client-secret',
                    'redirectUri' => 'https://example.test/callback',
                    'urlAuthorize' => 'https://example.test/authorize',
                    'urlAccessToken' => 'https://example.test/token',
                    'urlResourceOwnerDetails' => 'https://example.test/me',
                ],
            ],
        ]);
        (new Oauth2ClientProvider($di))->register($di);

        $this->assertInstanceOf(GenericProvider::class, $di->get('oauth2Client'));
    }

    public function testOauth2GoogleProviderRegistersConfiguredProvider(): void
    {
        $di = $this->createDi([
            'oauth2' => [
                'google' => [
                    'clientId' => 'google-client',
                    'clientSecret' => 'google-secret',
                    'redirectUri' => 'https://example.test/google/callback',
                    'hostedDomain' => 'example.test',
                ],
            ],
        ]);
        (new Oauth2GoogleProvider($di))->register($di);

        $provider = $di->get('oauth2Google');
        $authorizationUrl = $provider->getAuthorizationUrl();

        $this->assertInstanceOf(Google::class, $provider);
        $this->assertStringContainsString('client_id=google-client', $authorizationUrl);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.test%2Fgoogle%2Fcallback', $authorizationUrl);
        $this->assertStringContainsString('hd=example.test', $authorizationUrl);
    }

    public function testOauth2FacebookProviderBuildsAbsoluteRedirectUriFromRequest(): void
    {
        $di = $this->createDi([
            'oauth2' => [
                'facebook' => [
                    'clientId' => 'facebook-client',
                    'clientSecret' => 'facebook-secret',
                    'redirectUri' => '/facebook/callback',
                    'graphApiVersion' => 'v18.0',
                ],
            ],
        ]);
        $di->set('session', new SessionManager());
        $di->set('request', new class extends Request {
            public function isSecure(): bool
            {
                return false;
            }

            public function getScheme(): string
            {
                return 'http';
            }

            public function getHttpHost(): string
            {
                return 'example.test';
            }

            public function getPort(): int
            {
                return 8080;
            }
        });
        (new Oauth2FacebookProvider($di))->register($di);

        $provider = $di->get('oauth2Facebook');
        $authorizationUrl = $provider->getAuthorizationUrl();

        $this->assertInstanceOf(Facebook::class, $provider);
        $this->assertStringContainsString('client_id=facebook-client', $authorizationUrl);
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2Fexample.test%3A8080%2Ffacebook%2Fcallback', $authorizationUrl);
    }

    public function testOauth2FacebookProviderAllowsMissingRedirectUriWithoutWarnings(): void
    {
        $di = $this->createBareDi([
            'oauth2' => [
                'facebook' => [
                    'clientId' => 'facebook-client',
                    'clientSecret' => 'facebook-secret',
                    'graphApiVersion' => 'v18.0',
                ],
            ],
        ]);
        $di->set('session', new SessionManager());
        $di->set('request', new class extends Request {
            public function isSecure(): bool
            {
                return true;
            }

            public function getScheme(): string
            {
                return 'https';
            }

            public function getHttpHost(): string
            {
                return 'secure.example.test';
            }

            public function getPort(): int
            {
                return 443;
            }
        });
        (new Oauth2FacebookProvider($di))->register($di);

        $this->withoutPhpWarnings(function () use ($di): void {
            $provider = $di->get('oauth2Facebook');
            $authorizationUrl = $provider->getAuthorizationUrl();

            $this->assertInstanceOf(Facebook::class, $provider);
            $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fsecure.example.test', $authorizationUrl);
        });
    }

    public function testOpenAiProviderRegistersClientWithoutNetworkCall(): void
    {
        $di = $this->createDi([
            'openai' => [
                'apiKey' => 'test-key',
                'organization' => 'test-org',
                'project' => 'test-project',
                'baseUri' => 'https://api.openai.test/v1',
            ],
        ]);
        (new OpenAiProvider($di))->register($di);

        $this->assertInstanceOf(ClientContract::class, $di->get('openAi'));
    }

    public function testReCaptchaProviderRegistersVerifier(): void
    {
        $di = $this->createDi([
            'reCaptcha' => [
                'secret' => 'test-secret',
                'expectedHostname' => 'example.test',
                'expectedAction' => 'submit',
                'scoreThreshold' => 0.7,
            ],
        ]);
        (new ReCaptchaProvider($di))->register($di);

        $this->assertInstanceOf(ReCaptcha::class, $di->get('reCaptcha'));
    }

    public function testRedisProviderRegistersSharedRedisInstance(): void
    {
        if (!class_exists(Redis::class)) {
            $this->markTestSkipped('Redis extension is not available.');
        }

        $probe = new Redis();
        try {
            $connected = $probe->connect('127.0.0.1', 6379, 0.01);
        }
        catch (\RedisException $exception) {
            $this->markTestSkipped('Redis service is not available: ' . $exception->getMessage());
        }

        if (!$connected) {
            $this->markTestSkipped('Redis service is not available.');
        }

        if ($probe->isConnected()) {
            $probe->close();
        }

        $di = $this->createDi([
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'timeout' => 0.01,
                'persistentId' => null,
                'retryInterval' => 0,
                'readTimeout' => 0.01,
                'context' => null,
                'auth' => null,
                'database' => null,
                'options' => [],
            ],
        ]);
        (new RedisProvider($di))->register($di);

        $redis = $di->get('redis');

        $this->assertInstanceOf(Redis::class, $redis);
        $this->assertSame($redis, $di->get('redis'));

        if ($redis->isConnected()) {
            $redis->close();
        }
    }

    public function testRedisProviderAttemptsConfiguredAuth(): void
    {
        if (!class_exists(Redis::class)) {
            $this->markTestSkipped('Redis extension is not available.');
        }

        $di = $this->createDi([
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 0,
                'timeout' => 0.01,
                'persistentId' => null,
                'retryInterval' => 0,
                'readTimeout' => 0.01,
                'context' => null,
                'auth' => 'secret',
                'options' => [],
            ],
        ]);
        (new RedisProvider($di))->register($di);

        $this->expectException(\RedisException::class);

        $di->get('redis');
    }

    public function testRedisProviderAttemptsConfiguredDatabaseSelection(): void
    {
        if (!class_exists(Redis::class)) {
            $this->markTestSkipped('Redis extension is not available.');
        }

        $di = $this->createDi([
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 0,
                'timeout' => 0.01,
                'persistentId' => null,
                'retryInterval' => 0,
                'readTimeout' => 0.01,
                'context' => null,
                'database' => -1,
                'options' => [],
            ],
        ]);
        (new RedisProvider($di))->register($di);

        $this->expectException(\RedisException::class);

        $di->get('redis');
    }

    public function testRouterProviderRegistersCliRouterWithConfiguredDefaults(): void
    {
        $di = $this->createDi();
        $this->bootstrap->mode = Bootstrap::MODE_CLI;
        $this->bootstrap->router = null;
        $this->bootstrap->setConfig(new Config([
            'router' => [
                'cli' => [
                    'module' => 'unit',
                    'task' => 'smoke',
                    'action' => 'run',
                ],
            ],
        ]));
        $di->set('bootstrap', $this->bootstrap);
        (new RouterProvider($di))->register($di);

        $router = $di->get('router');
        $router->handle([]);

        $this->assertInstanceOf(CliRouter::class, $router);
        $this->assertSame('unit', $router->getModuleName());
        $this->assertSame('smoke', $router->getTaskName());
        $this->assertSame('run', $router->getActionName());
        $this->assertSame($di, $router->getDI());
    }

    public function testRouterProviderRegistersWsRouterWithConfiguredDefaults(): void
    {
        $di = $this->createDi();
        $this->bootstrap->mode = Bootstrap::MODE_WS;
        $this->bootstrap->router = null;
        $this->bootstrap->setConfig(new Config([
            'router' => [
                'ws' => [
                    'module' => 'socket',
                    'task' => 'events',
                    'action' => 'listen',
                ],
            ],
        ]));
        $di->set('bootstrap', $this->bootstrap);
        (new RouterProvider($di))->register($di);

        $router = $di->get('router');
        $router->handle([]);

        $this->assertInstanceOf(WsRouter::class, $router);
        $this->assertSame('socket', $router->getModuleName());
        $this->assertSame('events', $router->getTaskName());
        $this->assertSame('listen', $router->getActionName());
        $this->assertSame($di, $router->getDI());
    }

    public function testSessionProviderRegistersNoopSessionAndAppliesIni(): void
    {
        $originalSessionName = ini_get('session.name');
        $sessionName = 'PKUNIT' . bin2hex(random_bytes(4));
        $di = $this->createDi([
            'session' => [
                'driver' => 'noop',
                'drivers' => [
                    'noop' => [
                        'adapter' => SessionNoop::class,
                    ],
                ],
                'default' => [
                    'uniqueId' => 'unit_',
                ],
                'ini' => [
                    'session.name' => $sessionName,
                    'session.auto_start' => '0',
                ],
            ],
        ]);
        (new SessionProvider($di))->register($di);

        $session = null;
        try {
            $session = $di->get('session');

            $this->assertInstanceOf(SessionManager::class, $session);
            $this->assertInstanceOf(SessionNoop::class, $session->getAdapter());
            $this->assertSame($sessionName, $session->getName());
            $this->assertSame($sessionName, ini_get('session.name'));
        } finally {
            if ($session instanceof SessionManager) {
                $session->destroy();
            }
            ini_set('session.name', $originalSessionName);
        }
    }

    public function testSessionProviderDefaultsToStreamSessionWithoutWarnings(): void
    {
        $di = $this->createBareDi();
        (new SessionProvider($di))->register($di);

        $session = null;
        try {
            $this->withoutPhpWarnings(function () use ($di, &$session): void {
                $session = $di->get('session');

                $this->assertInstanceOf(SessionManager::class, $session);
                $this->assertInstanceOf(SessionStream::class, $session->getAdapter());
            });
        }
        finally {
            if ($session instanceof SessionManager && $session->exists()) {
                $session->destroy();
            }
        }
    }

    public function testSessionProviderDestroysExistingSessionBeforeStartingConfiguredSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $di = $this->createDi([
            'session' => [
                'driver' => 'noop',
                'drivers' => [
                    'noop' => [
                        'adapter' => SessionNoop::class,
                    ],
                ],
                'default' => [],
                'ini' => [],
            ],
        ]);
        (new SessionProvider($di))->register($di);

        $session = null;
        try {
            $session = $di->get('session');

            $this->assertInstanceOf(SessionManager::class, $session);
            $this->assertInstanceOf(SessionNoop::class, $session->getAdapter());
        } finally {
            if ($session instanceof SessionManager && $session->exists()) {
                $session->destroy();
            }
        }
    }

    public function testSessionProviderRegistersFactoryBackedSessionAdapter(): void
    {
        $adapter = $this->createFakeSessionAdapterClass();
        $di = $this->createDi([
            'session' => [
                'driver' => 'unit',
                'drivers' => [
                    'unit' => [
                        'adapter' => $adapter,
                    ],
                ],
                'default' => [],
                'ini' => [],
            ],
        ]);
        (new SessionProvider($di))->register($di);

        $session = null;
        try {
            $session = $di->get('session');

            $this->assertInstanceOf(SessionManager::class, $session);
            $this->assertInstanceOf($adapter, $session->getAdapter());
        } finally {
            if ($session instanceof SessionManager && $session->exists()) {
                $session->destroy();
            }
        }
    }

    public function testSessionProviderConfiguresRedisSessionIniForRedisAdapter(): void
    {
        $originalSaveHandler = ini_get('session.save_handler');
        $originalSavePath = ini_get('session.save_path');
        $adapter = $this->createFakeRedisSessionAdapterClass();
        $di = $this->createDi([
            'session' => [
                'driver' => 'redis',
                'drivers' => [
                    'redis' => [
                        'adapter' => $adapter,
                        'host' => '127.0.0.1',
                        'port' => 6379,
                        'prefix' => 'unit_',
                    ],
                ],
                'default' => [],
                'ini' => [],
            ],
        ]);
        (new SessionProvider($di))->register($di);

        $session = null;
        try {
            $session = $di->get('session');

            $this->assertInstanceOf(SessionManager::class, $session);
            $this->assertInstanceOf($adapter, $session->getAdapter());
            $this->assertStringContainsString('127.0.0.1:6379?', (string)ini_get('session.save_path'));
            $this->assertStringContainsString('prefix=unit_', (string)ini_get('session.save_path'));
        } finally {
            if ($session instanceof SessionManager && $session->exists()) {
                $session->destroy();
            }
            if ($originalSaveHandler !== 'user') {
                ini_set('session.save_handler', $originalSaveHandler);
            }
            ini_set('session.save_path', $originalSavePath);
        }
    }

    public function testSwooleProviderReportsMissingExtension(): void
    {
        $di = $this->createDi();
        (new SwooleProvider($di))->register($di);

        if (extension_loaded('swoole') && defined('SWOOLE_LOG_WARNING')) {
            $this->markTestSkipped('Swoole is available in this environment.');
        }

        if (function_exists('PhalconKit\\Provider\\Swoole\\extension_loaded')) {
            $this->markTestSkipped('Swoole provider doubles are already installed.');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Swoole not available');

        $di->get('swoole');
    }

    public function testSwooleProviderBuildsServerWithConfiguredDefaults(): void
    {
        if (extension_loaded('swoole')) {
            $this->markTestSkipped('Real Swoole extension is available.');
        }

        $this->installSwooleProviderDoubles();
        $di = $this->createDi([
            'swoole' => [
                'host' => '127.0.0.1',
                'port' => 9502,
                'settings' => [
                    'worker_num' => 3,
                ],
            ],
        ]);
        (new SwooleProvider($di))->register($di);

        $server = $di->get('swoole');

        $this->assertSame('127.0.0.1', $server->host);
        $this->assertSame(9502, $server->port);
        $this->assertSame(3, $server->settings['worker_num']);
        $this->assertSame(1000, $server->settings['max_conn']);
        $this->assertFalse($server->settings['daemonize']);
        $this->assertSame(60, $server->settings['heartbeat_check_interval']);
        $this->assertSame(120, $server->settings['heartbeat_idle_time']);
        $this->assertSame(SWOOLE_LOG_WARNING, $server->settings['log_level']);
        $this->assertSame(0, $server->settings['trace_flags']);
    }

    public function testVoltProviderRegistersEngineForConfiguredView(): void
    {
        $di = $this->createDi([
            'volt' => [
                'path' => '/tmp',
                'separator' => '_',
            ],
        ]);
        $di->set('view', new View());
        (new VoltProvider($di))->register($di);

        $this->assertInstanceOf(\Phalcon\Mvc\View\Engine\Volt::class, $di->get('volt'));
    }

    private function createDi(array $config = []): Di
    {
        $di = new Di();
        $di->set('config', new Config($config));

        return $di;
    }

    private function createBareDi(array $config = []): Di
    {
        $di = new Di();
        $di->set('config', new \PhalconKit\Config\Config($config));

        return $di;
    }

    private function withoutPhpWarnings(\Closure $callback): void
    {
        $handlerActive = true;
        set_error_handler(
            static function (int $code, string $message, string $file, int $line) use (&$handlerActive): never {
                $handlerActive = false;
                restore_error_handler();

                throw new \ErrorException($message, 0, $code, $file, $line);
            },
            E_WARNING
        );

        try {
            $callback();
        }
        finally {
            if ($handlerActive) {
                restore_error_handler();
            }
        }
    }

    private function createFakePdoAdapterClass(): string
    {
        return get_class(new class ([]) extends AbstractPdo {
            public $descriptor;

            public function __construct(array $descriptor)
            {
                $this->descriptor = $descriptor;
            }

            public function getDsnDefaults(): array
            {
                return [];
            }

            public function describeColumns(string $table, ?string $schema = null): array
            {
                return [];
            }
        });
    }

    private function createFakeDialectClass(): string
    {
        return get_class(new class extends \Phalcon\Db\Dialect\Mysql {
        });
    }

    private function createFakeMetadataAdapterClass(): string
    {
        return get_class(new class {
            /** @var array<string, mixed> */
            public array $options;

            public function __construct(mixed $factory = null, array $options = [])
            {
                $this->options = $options;
            }
        });
    }

    private function createFakeSessionAdapterClass(): string
    {
        return get_class(new class implements \SessionHandlerInterface {
            public function __construct(mixed $factory = null, array $options = [])
            {
            }

            public function open(string $path, string $name): bool
            {
                return true;
            }

            public function close(): bool
            {
                return true;
            }

            public function read(string $id): string|false
            {
                return '';
            }

            public function write(string $id, string $data): bool
            {
                return true;
            }

            public function destroy(string $id): bool
            {
                return true;
            }

            public function gc(int $max_lifetime): int|false
            {
                return 1;
            }
        });
    }

    private function createFakeRedisSessionAdapterClass(): string
    {
        return get_class(new class (null, []) extends SessionRedis {
            public function __construct($factory = null, array $options = [])
            {
            }

            public function open($path, $name): bool
            {
                return true;
            }

            public function close(): bool
            {
                return true;
            }

            public function read($id): string
            {
                return '';
            }

            public function write($id, $data): bool
            {
                return true;
            }

            public function destroy($id): bool
            {
                return true;
            }

            public function gc(int $max_lifetime): int|false
            {
                return 1;
            }
        });
    }

    private function installSwooleProviderDoubles(): void
    {
        if (!defined('SWOOLE_LOG_WARNING')) {
            define('SWOOLE_LOG_WARNING', 2);
        }

        if (!function_exists('PhalconKit\\Provider\\Swoole\\extension_loaded')) {
            eval(<<<'PHP'
namespace PhalconKit\Provider\Swoole;

function extension_loaded(string $extension): bool
{
    return $extension === 'swoole' || \extension_loaded($extension);
}

function defined(string $constant): bool
{
    return $constant === 'SWOOLE_LOG_WARNING' || \defined($constant);
}
PHP);
        }

        if (!class_exists('Swoole\\WebSocket\\Server')) {
            eval(<<<'PHP'
namespace Swoole\WebSocket;

class Server
{
    public array $settings = [];

    public function __construct(public string $host, public int $port)
    {
    }

    public function set(array $settings): void
    {
        $this->settings = $settings;
    }
}
PHP);
        }
    }
}
