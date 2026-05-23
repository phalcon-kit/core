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

namespace PhalconKit\Provider\Jwt;

use Phalcon\Encryption\Security\JWT\Builder;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;
use Phalcon\Encryption\Security\JWT\Signer\Hmac;
use Phalcon\Encryption\Security\JWT\Token\Parser;
use Phalcon\Encryption\Security\JWT\Signer\AbstractSigner;
use Phalcon\Encryption\Security\JWT\Token\Token;
use Phalcon\Encryption\Security\JWT\Validator;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Exception\ServiceException;

/**
 * Issue, parse and validate JSON Web Tokens (JWT) as described in RFC 7519.
 *
 * Builder (Phalcon\Encryption\Security\JWT\Builder)
 * Parser (Phalcon\Encryption\Security\JWT\Token\Parser)
 * Validator (Phalcon\Encryption\Security\JWT\Validator)
 */
class Jwt
{
    public array $options;
    
    public AbstractSigner $signer;
    
    public ?Builder $builder = null;
    
    public ?Parser $parser = null;
    
    public ?Validator $validator = null;
    
    public ?Token $token = null;
    
    public function __construct(array $defaultOptions = [])
    {
        $this->options = $defaultOptions;
        $this->signer();
    }
    
    /**
     * Initialize JWT Signer
     */
    public function signer(?string $signer = null, ?string $algo = null): AbstractSigner
    {
        $signer ??= $this->options['signer'] ?? Hmac::class;
        $algo ??= $this->options['algo'] ?? 'sha512';
        $this->signer = $this->createSigner($signer, $algo);
        return $this->signer;
    }
    
    /**
     * Initialize JWT Builder and validate it
     * @throws ValidatorException
     */
    public function builder(array $options = []): Builder
    {
        $options = $this->getDefaultOptions($options);
        
        $this->builder = new Builder($this->signer);
        $this->builder->setPassphrase($options['passphrase']);
        $this->builder->setExpirationTime($options['expiration']);
        $this->builder->setNotBefore($options['notBefore']);
        $this->builder->setIssuedAt($options['issuedAt']);
        $this->builder->setIssuer($options['issuer']);
        $this->builder->setAudience($options['audience']);
        $this->builder->setContentType($options['contentType']);
        $this->builder->setId($options['id']);
        $this->builder->setSubject($options['subject']);
        
        return $this->builder;
    }
    
    /**
     * Initialize JWT Parser
     */
    public function parser(): Parser
    {
        $this->parser = new Parser();
        return $this->parser;
    }
    
    /**
     * Initialize JWT Validator
     */
    public function validator(?Token $token = null, int $timeShift = 0): Validator
    {
        $token = $this->requireToken($token ?? $this->token);
        $this->validator = new Validator($token, $timeShift);
        return $this->validator;
    }
    
    /**
     * Build a token and validate it
     * @throws ValidatorException
     */
    public function buildToken(?Builder $builder = null): Token
    {
        $builder = $this->requireBuilder($builder ?? $this->builder);
        $this->token = $builder->getToken();
        return $this->token;
    }
    
    /**
     * Parse a jwt token and return the Token object
     */
    public function parseToken(string $token): Token
    {
        // fix phalcon error
        // https://github.com/phalcon/cphalcon/blob/bf9b70cee49afcccd10cfee783218ead2419d8ef/phalcon/Encryption/Security/JWT/Token/Parser.zep#L166
        // https://github.com/phalcon/cphalcon/issues/15608#issuecomment-1359323119
        // resolved in: https://github.com/phalcon/cphalcon/pull/16381
//        json_encode(null);
        
        $this->token = $this->parser()->parse($token);
        return $this->token;
    }
    
    /**
     * Validate the token
     * @throws ValidatorException|\DateMalformedStringException
     */
    public function validateToken(?Token $token = null, int $timeShift = 0, array $options = [], ?AbstractSigner $signer = null): array
    {
        $token ??= $this->token;
        $signer ??= $this->signer;
        $now = new \DateTimeImmutable();
        $options['expiration'] ??= $now->getTimestamp();
        $options['notBefore'] ??= $now->modify('-10 second')->getTimestamp();
        $options['issuedAt'] ??= $now->modify('+10 second')->getTimestamp();
        $options = $this->getDefaultOptions($options);
        
        $this->validator = $this->validator($token, $timeShift);
        
        $this->validator->validateId($options['id']);
        $this->validator->validateIssuer($options['issuer']);
        $this->validator->validateAudience($options['audience']);
        $this->validator->validateNotBefore($options['notBefore']);
        $this->validator->validateExpiration($options['expiration']);
        $this->validator->validateIssuedAt($options['issuedAt']);
        $this->validator->validateSignature($signer, $options['passphrase']);
        
        return $this->validator->getErrors();
    }
    
    /**
     * Get default JWT Builder Options
     */
    public function getDefaultOptions(array $options = []): array
    {
        $now = new \DateTimeImmutable();
        $options['expiration'] ??= $this->options['expiration'] ?? $now->modify('+1 day')->getTimestamp();
        $options['notBefore'] ??= $this->options['notBefore'] ?? $now->modify('-1 minute')->getTimestamp();
        $options['issuedAt'] ??= $this->options['issuedAt'] ?? $now->modify('now')->getTimestamp();
        $options['issuer'] ??= $this->options['issuer'] ?? '';
        $options['audience'] ??= $this->options['audience'] ?? '';
        $options['contentType'] ??= $this->options['contentType'] ?? '';
        $options['passphrase'] ??= $this->options['passphrase'] ?? '';
        $options['id'] ??= $this->options['id'] ?? '';
        $options['subject'] ??= $this->options['subject'] ?? '';
        
        return $options;
    }

    private function createSigner(string $signer, string $algo): AbstractSigner
    {
        if (!is_a($signer, AbstractSigner::class, true)) {
            throw new ConfigurationException(sprintf(
                'Invalid JWT signer "%s": expected a class-string of "%s".',
                $signer,
                AbstractSigner::class
            ));
        }

        /**
         * @var class-string<AbstractSigner> $signer
         * @psalm-suppress UnsafeInstantiation Phalcon JWT signers accept the algorithm in the constructor.
         */
        return new $signer($algo);
    }

    private function requireToken(?Token $token): Token
    {
        if (!$token instanceof Token) {
            throw new ServiceException(
                'Cannot initialize JWT validator without a token. Call parseToken() or buildToken() first, or pass a Token instance.'
            );
        }

        return $token;
    }

    private function requireBuilder(?Builder $builder): Builder
    {
        if (!$builder instanceof Builder) {
            throw new ServiceException(
                'Cannot build JWT token without a builder. Call builder() first, or pass a Builder instance.'
            );
        }

        return $builder;
    }
}
