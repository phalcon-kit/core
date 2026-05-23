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

namespace PhalconKit\Tests\Unit;

use Phalcon\Di\Di as PhalconDi;
use Phalcon\Html\Escaper as NativeEscaper;
use PhalconKit\Assets\Manager;
use PhalconKit\Di\Di;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Html\Escaper;
use PhalconKit\Html\TagFactory;
use PhalconKit\Tag;

class TagTest extends AbstractUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        PhalconDi::setDefault($this->di);
        Tag::setDI($this->di);
        Tag::setAssetsManager(null);
    }

    protected function tearDown(): void
    {
        Tag::setAssetsManager(null);
        if ($this->di instanceof Di) {
            PhalconDi::setDefault($this->di);
            Tag::setDI($this->di);
        }

        parent::tearDown();
    }

    public function testGetAssetsManagerReturnsConfiguredManager(): void
    {
        $manager = new Manager(new TagFactory(new Escaper()));
        Tag::setAssetsManager($manager);

        $this->assertSame($manager, Tag::getAssetsManager());
    }

    public function testGetAssetsManagerUsesTagDiContainer(): void
    {
        $previousDefault = PhalconDi::getDefault();
        $di = new Di();
        $manager = new Manager(new TagFactory(new Escaper()));
        $di->set('assets', $manager);
        Tag::setDI($di);
        Tag::setAssetsManager(null);

        try {
            PhalconDi::reset();
            $this->assertSame($manager, Tag::getAssetsManager());
        }
        finally {
            if ($previousDefault !== null) {
                PhalconDi::setDefault($previousDefault);
            }
        }
    }

    public function testGetAssetsManagerFailsClearlyWhenServiceIsMissing(): void
    {
        $di = new Di();
        PhalconDi::setDefault($di);
        Tag::setDI($di);
        Tag::setAssetsManager(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Could not resolve DI service "assets" for PhalconKit tag helpers.');

        Tag::getAssetsManager();
    }

    public function testGetAssetsManagerFailsClearlyWhenServiceHasWrongType(): void
    {
        $di = new Di();
        $di->set('assets', new \stdClass());
        PhalconDi::setDefault($di);
        Tag::setDI($di);
        Tag::setAssetsManager(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "assets" to be an instance of "PhalconKit\Assets\Manager"; got "stdClass".'
        );

        Tag::getAssetsManager();
    }

    public function testGetEscaperServiceFailsClearlyWhenServiceIsMissing(): void
    {
        $di = new Di();
        PhalconDi::setDefault($di);
        Tag::setDI($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Could not resolve DI service "escaper" for PhalconKit tag helpers.');

        Tag::getEscaperService();
    }

    public function testGetEscaperServiceFailsClearlyWhenServiceHasWrongType(): void
    {
        $di = new Di();
        $di->set('escaper', new \stdClass());
        PhalconDi::setDefault($di);
        Tag::setDI($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "escaper" to be an instance of "Phalcon\Html\Escaper\EscaperInterface"; got "stdClass".'
        );

        Tag::getEscaperService();
    }

    public function testEscapeParamRequiresPhalconKitEscaperContractForJsonEscaping(): void
    {
        $di = new Di();
        $di->set('escaper', new NativeEscaper());
        PhalconDi::setDefault($di);
        Tag::setDI($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "escaper" to be an instance of "PhalconKit\Html\Escaper\EscaperInterface";'
        );

        Tag::escapeParam(['bad' => 'value'], 'data-test');
    }

    public function testEscapeParamUsesConfiguredPhalconKitEscaper(): void
    {
        $di = new Di();
        $di->set('escaper', new Escaper());
        PhalconDi::setDefault($di);
        Tag::setDI($di);

        $this->assertSame(['alpha&quot;beta', 'data-test'], Tag::escapeParam('alpha"beta', 'data-test'));
    }

    public function testEscapeParamAllowsNullAttribute(): void
    {
        $di = new Di();
        $di->set('escaper', new Escaper());
        PhalconDi::setDefault($di);
        Tag::setDI($di);

        $this->assertSame(['alpha&lt;beta', null], Tag::escapeParam('alpha<beta'));
    }
}
