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

namespace PhalconKit\Tests\Unit\Mvc\Model\Traits;

use Phalcon\Encryption\Security as PhalconSecurity;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Encryption\Security;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\HashModelDouble;
use PhalconKit\Tests\Unit\AbstractUnit;

class HashTest extends AbstractUnit
{
    public function testHashAndCheckHashUseConfiguredServices(): void
    {
        $model = $this->createHashModel($this->createDi([
            'security' => [
                'salt' => 'unit-salt:',
                'workFactor' => 4,
            ],
        ]));

        $hash = $model->hash('secret');

        $this->assertTrue($model->checkHash($hash, 'secret'));
        $this->assertFalse($model->checkHash($hash, 'wrong-secret'));
        $this->assertFalse($model->checkHash(null, 'secret'));
        $this->assertFalse($model->checkHash($hash, null));
    }

    public function testHashRejectsInvalidConfigService(): void
    {
        $di = $this->createDi();
        $di->set('config', new \stdClass());
        $model = $this->createHashModel($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "config" to be an instance of "PhalconKit\Config\ConfigInterface"; got "stdClass".'
        );

        $model->hash('secret');
    }

    public function testCheckHashRejectsInvalidSecurityService(): void
    {
        $di = $this->createDi();
        $di->set('security', new \stdClass());
        $model = $this->createHashModel($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "security" to be an instance of "PhalconKit\Encryption\Security"; got "stdClass".'
        );

        $model->checkHash('non-empty-hash', 'secret');
    }

    /**
     * Creates a PhalconKit DI with config and security services for hash tests.
     *
     * @param array<string, mixed> $config Config data exposed as the `config`
     *     service.
     */
    private function createDi(array $config = []): Di
    {
        $di = new Di();
        $di->set('config', new Config($config));

        $security = new Security();
        $security->setDI($di);
        $security->setDefaultHash(PhalconSecurity::CRYPT_BCRYPT);
        $di->set('security', $security);

        return $di;
    }

    private function createHashModel(Di $di): HashModelDouble
    {
        return new HashModelDouble($di);
    }
}
