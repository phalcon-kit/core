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
 * Helper around Phalcon's JWT builder, parser, signer, and validator services.
 *
 * The helper keeps the most recently created builder/parser/validator/token on
 * the instance so existing identity flows can build, parse, and validate a
 * token in multiple steps. Applications normally receive this service from the
 * `jwt` DI service configured by `Provider\Jwt\ServiceProvider`.
 */
class Jwt
{
    /**
     * Default JWT options used when a method does not receive an explicit
     * override.
     *
     * Recognized keys include `signer`, `algo`, `expiration`, `notBefore`,
     * `issuedAt`, `issuer`, `audience`, `contentType`, `passphrase`, `id`,
     * and `subject`.
     *
     * @var array<string, mixed>
     */
    public array $options;
    
    /**
     * Current signer used by new builders and signature validation.
     */
    public AbstractSigner $signer;
    
    /**
     * Most recently initialized JWT builder, if any.
     */
    public ?Builder $builder = null;
    
    /**
     * Most recently initialized JWT parser, if any.
     */
    public ?Parser $parser = null;
    
    /**
     * Most recently initialized JWT validator, if any.
     */
    public ?Validator $validator = null;
    
    /**
     * Most recently built or parsed token, if any.
     */
    public ?Token $token = null;

    /**
     * Create the JWT helper with default options and initialize its signer.
     *
     * @param array<string, mixed> $defaultOptions Defaults used by builder(),
     *        signer(), validateToken(), and getDefaultOptions().
     * @throws ConfigurationException When the configured signer class does not
     *         extend Phalcon's JWT AbstractSigner.
     */
    public function __construct(array $defaultOptions = [])
    {
        $this->options = $defaultOptions;
        $this->signer();
    }
    
    /**
     * Initialize and store the JWT signer.
     *
     * The signer class name may be passed directly, or read from
     * `$this->options['signer']`. When no signer is configured, Phalcon's HMAC
     * signer is used with the configured `algo` or `sha512`.
     *
     * @param string|null $signer Signer class name; it must extend
     *        AbstractSigner.
     * @param string|null $algo Hash algorithm passed to the signer
     *        constructor.
     * @throws ConfigurationException When the signer class does not extend
     *         AbstractSigner.
     */
    public function signer(?string $signer = null, ?string $algo = null): AbstractSigner
    {
        $signer ??= $this->options['signer'] ?? Hmac::class;
        $algo ??= $this->options['algo'] ?? 'sha512';
        $this->signer = $this->createSigner($signer, $algo);
        return $this->signer;
    }
    
    /**
     * Initialize and store a JWT builder using default and explicit options.
     *
     * Explicit options override constructor defaults. The resulting builder is
     * stored on `$this->builder` so buildToken() can be called without passing
     * the builder again.
     *
     * Recognized option keys are `passphrase`, `expiration`, `notBefore`,
     * `issuedAt`, `issuer`, `audience`, `contentType`, `id`, and `subject`.
     *
     * @param array<string, mixed> $options Builder option overrides.
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
     * Initialize and store a JWT parser.
     *
     * The parser is stored on `$this->parser` for consumers that inspect the
     * helper state after parseToken().
     */
    public function parser(): Parser
    {
        $this->parser = new Parser();
        return $this->parser;
    }
    
    /**
     * Initialize and store a JWT validator for a token.
     *
     * If no token is passed, the most recently built or parsed token is used.
     *
     * @param Token|null $token Token to validate, or null to use the current
     *        helper token.
     * @param int $timeShift Clock skew allowance passed to Phalcon's
     *        validator.
     * @throws ServiceException When no token is available.
     */
    public function validator(?Token $token = null, int $timeShift = 0): Validator
    {
        $token = $this->requireToken($token ?? $this->token);
        $this->validator = new Validator($token, $timeShift);
        return $this->validator;
    }
    
    /**
     * Build and store a token from a builder.
     *
     * If no builder is passed, the most recently initialized builder is used.
     *
     * @param Builder|null $builder Builder to use, or null to use the current
     *        helper builder.
     * @throws ServiceException When no builder is available.
     * @throws ValidatorException
     */
    public function buildToken(?Builder $builder = null): Token
    {
        $builder = $this->requireBuilder($builder ?? $this->builder);
        $this->token = $builder->getToken();
        return $this->token;
    }
    
    /**
     * Parse an encoded JWT and store the resulting token.
     *
     * @param string $token Encoded JWT string.
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
     * Validate a token using configured claims and signer settings.
     *
     * If no token or signer is passed, the helper uses the current token and
     * signer. The method returns Phalcon validator errors; an empty array means
     * the token satisfied every enabled validation.
     *
     * @param Token|null $token Token to validate, or null to use the current
     *        helper token.
     * @param int $timeShift Clock skew allowance passed to Phalcon's
     *        validator.
     * @param array<string, mixed> $options Validation option overrides.
     * @param AbstractSigner|null $signer Signer used for signature validation,
     *        or null to use the current helper signer.
     * @return array<int|string, mixed> Validator errors.
     * @throws ServiceException When no token is available.
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
     * Merge explicit JWT options with constructor defaults and safe fallbacks.
     *
     * The returned array always contains `expiration`, `notBefore`, `issuedAt`,
     * `issuer`, `audience`, `contentType`, `passphrase`, `id`, and `subject`.
     *
     * @param array<string, mixed> $options Explicit option overrides.
     * @return array<string, mixed>
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
