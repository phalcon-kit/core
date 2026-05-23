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

namespace PhalconKit\Tests\Unit\Encryption;

use Phalcon\Encryption\Security;
use PhalconKit\Encryption\Security\Random;
use PhalconKit\Tests\Unit\AbstractUnit;

class SecurityTest extends AbstractUnit
{
    protected \PhalconKit\Encryption\Security $security;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->security = $this->di->get('security');
    }
    
    public function testSecurityFromDi(): void
    {
        $this->assertInstanceOf(\PhalconKit\Encryption\Security::class, $this->security);
        $this->assertInstanceOf(\Phalcon\Encryption\Security::class, $this->security);
    }

    public function testGetRandomReturnsPhalconKitRandom(): void
    {
        $this->assertInstanceOf(Random::class, $this->security->getRandom());
        $this->assertInstanceOf(\Phalcon\Encryption\Security\Random::class, $this->security->getRandom());
    }

    public function testRandomUuidAliasesAndVersions(): void
    {
        $random = new Random();
        $uuidV4 = $random->uuidv4();
        $uuidV7 = $random->uuidv7();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuidV4
        );
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuidV7
        );
    }
    
    public function testArgonDefaultHash(): void
    {
        $this->assertEquals(\Phalcon\Encryption\Security::CRYPT_ARGON2ID, $this->security->getDefaultHash());
        
        $options = $this->getConfig()->pathToArray('security.argon2');
        $this->assertIsArray($options);
        $this->assertArrayHasKey('memoryCost', $options);
        $this->assertArrayHasKey('timeCost', $options);
        $this->assertArrayHasKey('threads', $options);
        
        $this->assertTrue(password_verify('test', $this->security->hash('test')));
        $this->assertTrue(password_verify('test', $this->security->hash('test', $options)));
        $this->assertStringStartsWith('$argon2id$', $this->security->hash('test'));
        $this->assertStringStartsWith('$argon2id$', $this->security->hash('test', $options));
        
        $this->security->setDefaultHash(\Phalcon\Encryption\Security::CRYPT_ARGON2I);
        $this->assertTrue(password_verify('test', $this->security->hash('test')));
        $this->assertTrue(password_verify('test', $this->security->hash('test', $options)));
        $this->assertStringStartsWith('$argon2i$', $this->security->hash('test'));
        $this->assertStringStartsWith('$argon2i$', $this->security->hash('test', $options));
    }

    public function testArgonHashAllowsMissingArgonConfigWithoutWarnings(): void
    {
        $this->di->set('config', new \PhalconKit\Config\Config([
            'security' => [],
        ]));

        $security = new \PhalconKit\Encryption\Security();
        $security->setDI($this->di);
        $security->setDefaultHash(Security::CRYPT_ARGON2ID);

        $this->withoutPhpWarnings(function () use ($security): void {
            $hash = $security->hash('test');

            $this->assertStringStartsWith('$argon2id$', $hash);
            $this->assertTrue(password_verify('test', $hash));
        });
    }
    
    /**
     * Test the `hash` method with a simple string and default options
     *
     * This test will:
     * - Create an instance of the `Security` class
     * - Use the `hash` method to obtain a hashed version of a test string
     * - Verify that the returned value is a string
     * - Assert that the length of the hash function output string is correct.
     */
    public function testHashMethodWithSimpleString(): void
    {
        // Arrange
        $security = new \PhalconKit\Encryption\Security();
        $testString = 'test';
        
        // Act
        $hash = $security->hash($testString);
        
        // Assert
        $this->assertIsString($hash);
        $this->assertEquals(60, strlen($hash));
        $this->assertNotEquals($testString, $hash);  // Assert that hashed string is different from the original string
        $this->assertTrue(password_verify($testString, $hash)); // Assert that original string verifies against the hash
    }
    
    /**
     * Test the `hash` method with non-default argon2 options.
     *
     * This test will:
     * - Define a configuration with non-default argon2 options
     * - Create an instance of the `Security` class with this configuration
     * - Use the `hash` method to obtain a hashed version of a test string
     * - Verify that the returned value is a string
     * - Assert that a change in cost parameters produces a different hash.
     */
    public function testHashMethodWithNonDefaultArgonOptions(): void
    {
        // Arrange
        $config = new \Phalcon\Config\Config([
            'security' => [
                'argon2' => [
                    'memoryCost' => 1024,
                    'timeCost' => 2,
                    'threads' => 2,
                ],
            ],
        ]);
        
        $this->di->set('config', $config);
        $security = new \PhalconKit\Encryption\Security();
        
        $testString = 'test';
        
        // Act
        $hash = $security->hash($testString);
        
        // Assert
        $this->assertIsString($hash);
        $this->assertNotEquals($testString, $hash);  // Assert that hashed string is different from the original string
        $this->assertTrue(password_verify($testString, $hash));  // Assert that original string verifies against the hash

        // Changing the configuration should produce a different hash
        $config['security']['argon2']['memoryCost'] = 2048;
        $config['security']['argon2']['timeCost'] = 3;
        $this->di->set('config', $config);
        $security = new \PhalconKit\Encryption\Security();

        $newHash = $security->hash($testString);

        $this->assertNotEquals($hash, $newHash);
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
}
