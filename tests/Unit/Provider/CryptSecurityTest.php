<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Provider;

use Phalcon\Encryption\Crypt;
use PhalconKit\Config\Config;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Provider\Crypt\ServiceProvider;
use PhalconKit\Tests\Unit\AbstractUnit;
use PHPUnit\Framework\Attributes\DataProvider;

final class CryptSecurityTest extends AbstractUnit
{
    public function testPrivateKeyDefaultsRoundTripAcrossInstancesAndRejectTampering(): void
    {
        $key = random_bytes(32);
        $writer = $this->crypt(['key' => $key]);
        $reader = $this->crypt(['key' => $key]);
        $ciphertext = $writer->encrypt('synthetic private content');
        self::assertSame('synthetic private content', $reader->decrypt($ciphertext));
        self::assertNotSame($ciphertext, $writer->encrypt('synthetic private content'));
        $ciphertext[strlen($ciphertext) - 1] = chr(ord($ciphertext[strlen($ciphertext) - 1]) ^ 1);
        $this->expectException(\Phalcon\Encryption\Crypt\Exception\Exception::class);
        $reader->decrypt($ciphertext);
    }

    public function testIndependentPrivateKeyCannotDecrypt(): void
    {
        $ciphertext = $this->crypt(['key' => random_bytes(32)])->encrypt('synthetic private content');
        $this->expectException(\Phalcon\Encryption\Crypt\Exception\Exception::class);
        $this->crypt(['key' => random_bytes(32)])->decrypt($ciphertext);
    }

    public function testBase64KeyUsesDecodedBytesAndRawKeysRemainCompatible(): void
    {
        $key = random_bytes(32);
        $ciphertext = $this->crypt(['key' => 'base64:' . base64_encode($key)])->encrypt('synthetic');
        self::assertSame('synthetic', $this->crypt(['key' => $key])->decrypt($ciphertext));
    }

    public function testCustomCbcConfigurationRetainsSigningFallback(): void
    {
        $options = ['key' => random_bytes(32), 'cipher' => 'aes-256-cbc'];
        $ciphertext = $this->crypt($options)->encrypt('synthetic');
        self::assertSame('synthetic', $this->crypt($options + ['useSigning' => true])->decrypt($ciphertext));
        $ciphertext[strlen($ciphertext) - 1] = chr(ord($ciphertext[strlen($ciphertext) - 1]) ^ 1);
        $this->expectException(\Phalcon\Encryption\Crypt\Exception\Exception::class);
        $this->crypt($options)->decrypt($ciphertext);
    }

    public static function unsafeOptions(): array
    {
        return [
            'missing' => [['key' => null]],
            'blank' => [['key' => str_repeat(' ', 32)]],
            'short' => [['key' => 'short']],
            'malformed base64' => [['key' => 'base64:!not-valid!']],
            'decoded short key' => [['key' => 'base64:' . base64_encode('short')]],
            'legacy public key' => [['key' => 'T4\xb1\x8d\xa9\x98\x05\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3']],
            'empty associated data' => [['key' => str_repeat('k', 32), 'authData' => '']],
        ];
    }

    #[DataProvider('unsafeOptions')]
    public function testUnsafeConfigurationFailsBeforeUse(array $options): void
    {
        $this->expectException(ConfigurationException::class);
        $this->crypt($options);
    }

    public function testExplicitAssociatedDataRemainsCompatible(): void
    {
        $options = ['key' => random_bytes(32), 'authData' => 'existing-application-context'];
        $ciphertext = $this->crypt($options)->encrypt('synthetic');
        self::assertSame('synthetic', $this->crypt($options)->decrypt($ciphertext));
        $this->expectException(\Phalcon\Encryption\Crypt\Exception\Exception::class);
        $this->crypt(['key' => $options['key']])->decrypt($ciphertext);
    }

    private function crypt(array $options): Crypt
    {
        $di = new \PhalconKit\Di\Di();
        $di->setShared('config', new Config(['crypt' => $options]));
        (new ServiceProvider($di))->register($di);
        return $di->get('crypt');
    }
}
