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
use League\OAuth2\Client\Provider\GenericProvider;
use OpenAI\Contracts\ClientContract;
use Phalcon\Annotations\Adapter\Memory as AnnotationsMemory;
use Phalcon\Di\Di;
use Phalcon\Encryption\Crypt;
use Phalcon\Events\Manager;
use Phalcon\Flash\Direct;
use Phalcon\Http\Response\Cookies;
use Phalcon\Mvc\Model\MetaData\Memory as MetadataMemory;
use PhalconKit\Acl\Acl;
use PhalconKit\Assets\Manager as AssetsManager;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Html\Escaper;
use PhalconKit\Identity\Manager as IdentityManager;
use PhalconKit\Mvc\View;
use PhalconKit\Provider\Acl\ServiceProvider as AclProvider;
use PhalconKit\Provider\Annotations\ServiceProvider as AnnotationsProvider;
use PhalconKit\Provider\Assets\ServiceProvider as AssetsProvider;
use PhalconKit\Provider\Aws\ServiceProvider as AwsProvider;
use PhalconKit\Provider\Cookies\ServiceProvider as CookiesProvider;
use PhalconKit\Provider\Crypt\ServiceProvider as CryptProvider;
use PhalconKit\Provider\Database\ServiceProvider as DatabaseProvider;
use PhalconKit\Provider\Flash\ServiceProvider as FlashProvider;
use PhalconKit\Provider\Identity\ServiceProvider as IdentityProvider;
use PhalconKit\Provider\Jwt\Jwt;
use PhalconKit\Provider\Jwt\ServiceProvider as JwtProvider;
use PhalconKit\Provider\LoremIpsum\ServiceProvider as LoremIpsumProvider;
use PhalconKit\Provider\ModelsMetadata\ServiceProvider as ModelsMetadataProvider;
use PhalconKit\Provider\OCR\ServiceProvider as OCRProvider;
use PhalconKit\Provider\Oauth2Client\ServiceProvider as Oauth2ClientProvider;
use PhalconKit\Provider\OpenAi\ServiceProvider as OpenAiProvider;
use PhalconKit\Provider\ReCaptcha\ServiceProvider as ReCaptchaProvider;
use PhalconKit\Provider\Swoole\ServiceProvider as SwooleProvider;
use PhalconKit\Provider\Volt\ServiceProvider as VoltProvider;
use PhalconKit\Tests\Unit\AbstractUnit;
use ReCaptcha\ReCaptcha;
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

    public function testModelsMetadataProviderRegistersMemoryAdapter(): void
    {
        $di = $this->createDi();
        $di->set('bootstrap', $this->bootstrap);
        (new ModelsMetadataProvider($di))->register($di);

        $this->assertInstanceOf(MetadataMemory::class, $di->get('modelsMetadata'));
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

    public function testSwooleProviderReportsMissingExtension(): void
    {
        $di = $this->createDi();
        (new SwooleProvider($di))->register($di);

        if (extension_loaded('swoole') && defined('SWOOLE_LOG_WARNING')) {
            $this->markTestSkipped('Swoole is available in this environment.');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Swoole not available');

        $di->get('swoole');
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
}
