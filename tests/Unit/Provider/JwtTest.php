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

use Phalcon\Encryption\Security\JWT\Builder;
use Phalcon\Encryption\Security\JWT\Signer\Hmac;
use Phalcon\Encryption\Security\JWT\Token\Parser;
use Phalcon\Encryption\Security\JWT\Token\Token;
use Phalcon\Encryption\Security\JWT\Validator;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Provider\Jwt\Jwt;
use PhalconKit\Tests\Unit\AbstractUnit;

class JwtTest extends AbstractUnit
{
    public function testDefaultOptionsMergeExplicitAndConfiguredValues(): void
    {
        $jwt = new Jwt([
            'issuer' => 'configured-issuer',
            'audience' => 'configured-audience',
            'subject' => 'configured-subject',
            'passphrase' => $this->passphrase(),
        ]);

        $options = $jwt->getDefaultOptions([
            'issuer' => 'explicit-issuer',
            'id' => 'explicit-id',
        ]);

        $this->assertSame('explicit-issuer', $options['issuer']);
        $this->assertSame('explicit-id', $options['id']);
        $this->assertSame('configured-audience', $options['audience']);
        $this->assertSame('configured-subject', $options['subject']);
        $this->assertSame($this->passphrase(), $options['passphrase']);
        $this->assertIsInt($options['expiration']);
        $this->assertIsInt($options['notBefore']);
        $this->assertIsInt($options['issuedAt']);
    }

    public function testSignerCanBeReinitializedWithConfiguredAlgorithm(): void
    {
        $jwt = new Jwt([
            'signer' => Hmac::class,
            'algo' => 'sha256',
        ]);

        $signer = $jwt->signer(Hmac::class, 'sha512');

        $this->assertInstanceOf(Hmac::class, $jwt->signer);
        $this->assertSame($jwt->signer, $signer);
    }

    public function testSignerRejectsInvalidSignerClass(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid JWT signer "stdClass": expected a class-string of');

        new Jwt([
            'signer' => \stdClass::class,
        ]);
    }

    public function testBuildParseAndValidateTokenRoundTrip(): void
    {
        $jwt = new Jwt($this->defaultJwtOptions());

        $builder = $jwt->builder();
        $token = $jwt->buildToken($builder);
        $encoded = $token->getToken();
        $parsed = $jwt->parseToken($encoded);
        $errors = $jwt->validateToken($parsed);

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertInstanceOf(Token::class, $token);
        $this->assertInstanceOf(Parser::class, $jwt->parser);
        $this->assertInstanceOf(Token::class, $parsed);
        $this->assertInstanceOf(Validator::class, $jwt->validator);
        $this->assertSame($encoded, $parsed->getToken());
        $this->assertSame([], $errors);
    }

    public function testBuilderUsesExplicitOverrides(): void
    {
        $jwt = new Jwt($this->defaultJwtOptions());
        $builder = $jwt->builder([
            'id' => 'override-id',
            'subject' => 'override-subject',
            'issuer' => 'override-issuer',
            'audience' => 'override-audience',
        ]);
        $token = $jwt->buildToken($builder);
        $claims = $token->getClaims()->getPayload();

        $this->assertSame('override-id', $claims['jti']);
        $this->assertSame('override-subject', $claims['sub']);
        $this->assertSame('override-issuer', $claims['iss']);
        $this->assertSame(['override-audience'], $claims['aud']);
    }

    public function testBuildTokenRejectsMissingBuilder(): void
    {
        $jwt = new Jwt($this->defaultJwtOptions());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Cannot build JWT token without a builder.');

        $jwt->buildToken();
    }

    public function testValidatorRejectsMissingToken(): void
    {
        $jwt = new Jwt($this->defaultJwtOptions());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Cannot initialize JWT validator without a token.');

        $jwt->validator();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultJwtOptions(): array
    {
        return (new Config())->pathToArray('security.jwt');
    }

    private function passphrase(): string
    {
        return $this->defaultJwtOptions()['passphrase'];
    }
}
