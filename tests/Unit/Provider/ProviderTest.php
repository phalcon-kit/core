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

use League\Flysystem\Filesystem;
use PhalconKit\Db\Profiler;
use PhalconKit\Filter\Filter;
use PhalconKit\Html\Escaper;
use PhalconKit\Http\Request;
use PhalconKit\Http\Response;
use PhalconKit\Logger\Loggers;
use PhalconKit\Mvc\Url;
use PhalconKit\Provider\ServiceProviderInterface;
use PhalconKit\Support\Utils;
use PhalconKit\Support\Version;
use PhalconKit\Tests\Unit\AbstractUnit;

class ProviderTest extends AbstractUnit
{
    public function testProvider(): void
    {
        $providers = $this->bootstrap->config->pathToArray('providers') ?? [];
        $this->assertIsArray($providers);
        
        foreach ($providers as $assumption => $concrete) {
            $this->assertIsString($assumption);
            $this->assertIsString($concrete);
            
            $provider = new $concrete($this->di);
            $this->assertInstanceOf(ServiceProviderInterface::class, $provider);
        }
    }

    public function testConfiguredProvidersAdvertiseStableUniqueServiceNames(): void
    {
        $providers = $this->bootstrap->config->pathToArray('providers') ?? [];
        $this->assertIsArray($providers);

        $serviceNames = [];
        foreach ($providers as $expectedProvider => $concreteProvider) {
            $this->assertIsString($expectedProvider);
            $this->assertIsString($concreteProvider);

            $provider = new $concreteProvider($this->di);
            $this->assertInstanceOf(ServiceProviderInterface::class, $provider);
            $this->assertNotSame('', $provider->getName());

            $serviceNames[$expectedProvider] = $provider->getName();
        }

        $this->assertSame(array_unique($serviceNames), $serviceNames);
    }
    
    public function testFileSystemProvider(): void
    {
        $fileSystem = $this->di->get('fileSystem');
        assert($fileSystem instanceof Filesystem);
        
        $this->assertInstanceOf(Filesystem::class, $fileSystem);
        $contents = $fileSystem->listContents('.');
        $this->assertIsArray($contents->toArray());
    }

    public function testCommonSupportProvidersResolveExpectedServices(): void
    {
        $this->assertInstanceOf(Filter::class, $this->di->get('filter'));
        $this->assertInstanceOf(Utils::class, $this->di->get('utils'));
        $this->assertInstanceOf(Version::class, $this->di->get('version'));
    }

    public function testCommonFrameworkProvidersResolveExpectedServices(): void
    {
        $this->assertInstanceOf(Escaper::class, $this->di->get('escaper'));
        $this->assertInstanceOf(Loggers::class, $this->di->get('loggers'));
        $this->assertInstanceOf(Profiler::class, $this->di->get('profiler'));
        $this->assertInstanceOf(Request::class, $this->di->get('request'));
        $this->assertInstanceOf(Response::class, $this->di->get('response'));
        $this->assertInstanceOf(Url::class, $this->di->get('url'));
    }
}
