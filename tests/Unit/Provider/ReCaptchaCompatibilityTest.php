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

use ArrayObject;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Provider\ReCaptcha\ServiceProvider;
use PhalconKit\Tests\Unit\AbstractUnit;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/** Verify the configured provider through reCAPTCHA 2.x without network requests. */
final class ReCaptchaCompatibilityTest extends AbstractUnit
{
    /** @throws \JsonException */
    public function testTypedTransportReceivesSecretTokenAndRemoteAddress(): void
    {
        $requests = new ArrayObject();
        $di = $this->createDi($this->transport($this->validResponse(), $requests));
        $verifier = $di->getShared('reCaptcha');
        $response = $verifier->verify('synthetic-token', '192.0.2.10');

        self::assertSame($verifier, $di->getShared('reCaptcha'));
        self::assertTrue($response->isSuccess());
        self::assertSame([], $response->getErrorCodes());
        self::assertSame([[
            'secret' => 'synthetic-secret',
            'response' => 'synthetic-token',
            'remoteip' => '192.0.2.10',
            'version' => ReCaptcha::VERSION,
        ]], $requests->getArrayCopy());
        self::assertSame($response->toArray(), json_decode(json_encode($response, JSON_THROW_ON_ERROR), true));
    }

    public function testZeroStringSecretAndTokenRemainValidInputs(): void
    {
        $requests = new ArrayObject();
        $di = $this->createDi($this->transport($this->validResponse(), $requests), ['secret' => '0']);

        self::assertTrue($di->getShared('reCaptcha')->verify('0')->isSuccess());
        self::assertSame('0', $requests[0]['secret']);
        self::assertSame('0', $requests[0]['response']);
        self::assertArrayNotHasKey('remoteip', $requests[0]);
    }

    public function testConfiguredExpectationsRejectMismatchedResponses(): void
    {
        foreach ([
            ['hostname', 'other.example.test', ReCaptcha::E_HOSTNAME_MISMATCH],
            ['apk_package_name', 'test.other', ReCaptcha::E_APK_PACKAGE_NAME_MISMATCH],
            ['action', 'other', ReCaptcha::E_ACTION_MISMATCH],
            ['score', 0.1, ReCaptcha::E_SCORE_THRESHOLD_NOT_MET],
        ] as [$field, $value, $error]) {
            $requests = new ArrayObject();
            $payload = array_replace($this->validResponse(), [$field => $value]);
            $di = $this->createDi($this->transport($payload, $requests));
            $response = $di->getShared('reCaptcha')->verify('synthetic-token');

            self::assertFalse($response->isSuccess(), $field);
            self::assertSame([$error], $response->getErrorCodes(), $field);
            self::assertCount(1, $requests);
        }
    }

    public function testMissingScoreFailsEvenWithZeroThreshold(): void
    {
        $requests = new ArrayObject();
        $payload = $this->validResponse();
        unset($payload['score']);
        $di = $this->createDi($this->transport($payload, $requests), ['scoreThreshold' => 0.0]);
        $response = $di->getShared('reCaptcha')->verify('synthetic-token');

        self::assertFalse($response->isSuccess());
        self::assertSame([ReCaptcha::E_SCORE_THRESHOLD_NOT_MET], $response->getErrorCodes());
    }

    public function testEmptyTokenDoesNotCallTransportAndEmptySecretStillFails(): void
    {
        $transport = $this->createMock(RequestMethod::class);
        $transport->expects(self::never())->method('submit');
        $verifier = $this->createDi($transport)->getShared('reCaptcha');

        self::assertSame([ReCaptcha::E_MISSING_INPUT_RESPONSE], $verifier->verify('')->getErrorCodes());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No secret provided');
        $this->createDi($transport, ['secret' => ''])->getShared('reCaptcha');
    }

    public function testImmutableExpectationsDoNotChangeTheSharedVerifier(): void
    {
        $requests = new ArrayObject();
        $di = $this->createDi($this->transport($this->validResponse(), $requests));
        $shared = $di->getShared('reCaptcha');
        $custom = $shared->withExpectedAction('other');

        self::assertNotSame($shared, $custom);
        self::assertSame([ReCaptcha::E_ACTION_MISMATCH], $custom->verify('synthetic-token')->getErrorCodes());
        self::assertTrue($di->getShared('reCaptcha')->verify('synthetic-token')->isSuccess());
        self::assertCount(2, $requests);
    }

    /** @param array<string, mixed> $options */
    private function createDi(RequestMethod $transport, array $options = []): Di
    {
        $di = new Di();
        $di->setShared('config', new Config(['reCaptcha' => array_replace([
            'secret' => 'synthetic-secret',
            'requestMethod' => $transport,
            'expectedHostname' => 'example.test',
            'expectedApkPackageName' => 'test.application',
            'expectedAction' => 'submit',
            'scoreThreshold' => 0.7,
        ], $options)]));
        (new ServiceProvider($di))->register($di);

        return $di;
    }

    /** @return array<string, mixed> */
    private function validResponse(): array
    {
        return [
            'success' => true,
            'hostname' => 'example.test',
            'apk_package_name' => 'test.application',
            'action' => 'submit',
            'score' => 0.9,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param ArrayObject<int, array<string, string>> $requests
     */
    private function transport(array $payload, ArrayObject $requests): RequestMethod
    {
        return new class ($payload, $requests) implements RequestMethod {
            public function __construct(private readonly array $payload, private readonly ArrayObject $requests)
            {
            }

            public function submit(RequestParameters $params): string
            {
                $this->requests->append($params->toArray());

                return json_encode($this->payload, JSON_THROW_ON_ERROR);
            }
        };
    }
}
