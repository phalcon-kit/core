<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Provider\Crypt;

use PhalconKit\Di\DiInterface;
use Phalcon\Encryption\Crypt;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the encryption service.
 *
 * The provider validates cipher, signing, padding, and key configuration before
 * returning Phalcon's `Crypt` service. It defaults to AES-256-GCM and requires
 * a private key of at least 32 bytes from `crypt.key` (`CRYPT_KEY`, with
 * `APP_CRYPT_KEY` as a fallback). A `base64:` prefix decodes binary key material
 * before use; unprefixed keys retain their existing byte representation.
 * The public legacy key is rejected. Default associated data is `phalcon-kit`;
 * applications must retain their configured associated data when decrypting
 * existing ciphertext. No key rotation or data migration is performed here.
 *
 * AEAD ciphers such as GCM/CCM authenticate internally and must not also enable
 * Phalcon's signing path. Stream modes are rejected with signing enabled
 * because Phalcon's HMAC signing path is not compatible with those modes.
 *
 * @see https://docs.phalcon.io/latest/encryption-crypt/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'crypt';
    
    /**
     * Register the shared `crypt` service.
     *
     * Runtime arguments can override cipher and signing for a specific
     * resolution, but all other options are read from `crypt` config. Invalid
     * cryptographic configuration fails during service resolution so the
     * application does not start with unsafe encryption settings.
     *
     * @throws ConfigurationException When the pad factory, encryption key,
     *     cipher, or signing mode is invalid.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?string $cipher = null, ?bool $useSigning = null) use ($di) {
            
            $config = $di->getConfig();
            $options = $config->pathToArray('crypt') ?? [];
            
            $cipher ??= $options['cipher'] ?? 'aes-256-gcm';
            $lowerCipher = strtolower($cipher);
            $isAEAD = str_ends_with($lowerCipher, '-gcm') || str_ends_with($lowerCipher, '-ccm');
            // Preserve the existing signing fallback for custom non-AEAD configurations.
            $useSigning ??= $options['useSigning'] ?? !$isAEAD;
            $hash = $options['hashAlgorithm'] ?? 'sha256';
            $key = $options['key'] ?? ($_ENV['CRYPT_KEY'] ?? $_ENV['APP_CRYPT_KEY'] ?? null);
            $padScheme = $options['padScheme'] ?? Crypt::PADDING_DEFAULT;
            $padFactoryClass = $options['padFactory'] ?? Crypt\PadFactory::class;
            if (!is_string($padFactoryClass) || !class_exists($padFactoryClass)) {
                throw new ConfigurationException('Invalid crypt pad factory: expected an existing class name.');
            }
            
            $authData = $options['authData'] ?? 'phalcon-kit';
            $authTag = $options['authTag'] ?? '';
            $authTagLength = $options['authTagLength'] ?? 16;
            
            $padFactory = new $padFactoryClass();
            if (!$padFactory instanceof Crypt\PadFactory) {
                throw new ConfigurationException(sprintf(
                    'Invalid crypt pad factory "%s": expected an instance of "%s".',
                    $padFactoryClass,
                    Crypt\PadFactory::class
                ));
            }
            
            if (is_string($key) && str_starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7), true);
            }
            // Validate decoded key material before allowing encryption.
            if (!is_string($key) || strlen($key) < 32 || trim($key) === '' || $key === 'T4\xb1\x8d\xa9\x98\x05\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3') {
                throw new ConfigurationException('Invalid encryption key: configure a private key of at least 32 bytes; the public legacy key is not accepted.');
            }
            
            // OpenSSL cipher availability depends on the PHP/OpenSSL build.
            $availableCiphers = openssl_get_cipher_methods(true);
            if (!in_array(strtolower($cipher), $availableCiphers, true)) {
                throw new ConfigurationException(sprintf(
                    'Invalid cipher "%s": not supported by the current OpenSSL build.',
                    $cipher
                ));
            }
            
            $isStreamMode = str_ends_with($lowerCipher, '-cfb')
                || str_ends_with($lowerCipher, '-ofb')
                || str_ends_with($lowerCipher, '-ctr');
            
            // AEAD handles authentication internally.
            if ($isAEAD && $useSigning) {
                throw new ConfigurationException(sprintf(
                    'Invalid configuration: cipher "%s" is AEAD (auth built-in). Disable "useSigning".',
                    $cipher
                ));
            }
            
            // Stream modes cannot be safely signed through Phalcon's HMAC path.
            if ($isStreamMode && $useSigning) {
                throw new ConfigurationException(sprintf(
                    'Invalid configuration: cipher "%s" does not support signing (stream mode). Disable "useSigning" or use CBC/GCM instead.',
                    $cipher
                ));
            }
            
            if ($isAEAD && (!is_string($authData) || $authData === '')) {
                throw new ConfigurationException('AEAD encryption requires non-empty crypt.authData.');
            }

            $crypt = new Crypt($cipher, $useSigning, $padFactory);
            $crypt->setKey($key);
            $crypt->setPadding($padScheme);
            
            if ($useSigning) {
                $crypt->setHashAlgorithm($hash);
            }
            
            $crypt->setAuthData($authData);
            $crypt->setAuthTag($authTag);
            $crypt->setAuthTagLength($authTagLength);
            
            return $crypt;
        });
    }
}
