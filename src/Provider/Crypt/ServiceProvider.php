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

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'crypt';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?string $cipher = null, ?bool $useSigning = null) use ($di) {
            
            $config = $di->getConfig();
            $options = $config->pathToArray('crypt') ?? [];
            
            $cipher ??= $options['cipher'] ?? 'aes-256-gcm';
            $useSigning ??= $options['useSigning'] ?? true;
            $hash = $options['hashAlgorithm'] ?? 'sha256';
            $key = $options['key'] ?? ($_ENV['APP_CRYPT_KEY'] ?? null);
            $padScheme = $options['padScheme'] ?? Crypt::PADDING_DEFAULT;
            $padFactoryClass = $options['padFactory'] ?? Crypt\PadFactory::class;
            if (!is_string($padFactoryClass) || !class_exists($padFactoryClass)) {
                throw new ConfigurationException('Invalid crypt pad factory: expected an existing class name.');
            }
            
            $authData = $options['authData'] ?? '';
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
            
            // -----------------------------------------------------------------
            // Validate key
            // -----------------------------------------------------------------
            if (empty($key) || strlen($key) < 32) {
                throw new ConfigurationException('Invalid encryption key: must be at least 32 bytes for AES-256 ciphers.');
            }
            
            // -----------------------------------------------------------------
            // Validate cipher existence
            // -----------------------------------------------------------------
            $availableCiphers = openssl_get_cipher_methods(true);
            if (!in_array(strtolower($cipher), $availableCiphers, true)) {
                throw new ConfigurationException(sprintf(
                    'Invalid cipher "%s": not supported by the current OpenSSL build.',
                    $cipher
                ));
            }
            
            // -----------------------------------------------------------------
            // Validate cipher mode vs signing
            // -----------------------------------------------------------------
            $lowerCipher = strtolower($cipher);
            
            $isAEAD = str_ends_with($lowerCipher, '-gcm') || str_ends_with($lowerCipher, '-ccm');
            $isStreamMode = str_ends_with($lowerCipher, '-cfb')
                || str_ends_with($lowerCipher, '-ofb')
                || str_ends_with($lowerCipher, '-ctr');
            
            // AEAD handles authentication internally
            if ($isAEAD && $useSigning) {
                throw new ConfigurationException(sprintf(
                    'Invalid configuration: cipher "%s" is AEAD (auth built-in). Disable "useSigning".',
                    $cipher
                ));
            }
            
            // Stream modes cannot be safely signed via Phalcon’s HMAC path
            if ($isStreamMode && $useSigning) {
                throw new ConfigurationException(sprintf(
                    'Invalid configuration: cipher "%s" does not support signing (stream mode). Disable "useSigning" or use CBC/GCM instead.',
                    $cipher
                ));
            }
            
            // -----------------------------------------------------------------
            // Build Crypt instance
            // -----------------------------------------------------------------
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
