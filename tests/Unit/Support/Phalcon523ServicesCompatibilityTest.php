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

namespace PhalconKit\Tests\Unit\Support;

use Phalcon\Container\Container;
use Phalcon\Container\Exceptions\CircularAliasFound;
use Phalcon\Container\Exceptions\FrozenDefinition;
use Phalcon\Support\Settings;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Di\DiInterface;
use PhalconKit\Di\FactoryDefault;
use PhalconKit\Di\FactoryDefault\Cli;
use PhalconKit\Filter\Filter;
use PhalconKit\Provider\Filter\ServiceProvider;
use PhalconKit\Tests\Unit\AbstractUnit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/** Protect Core service integration against Phalcon 5.22.1/5.23 optimizations. */
final class Phalcon523ServicesCompatibilityTest extends AbstractUnit
{
    /** @return array<string, array{class-string<DiInterface>}> */
    public static function containers(): array
    {
        return ['minimal' => [Di::class], 'MVC' => [FactoryDefault::class], 'CLI' => [Cli::class]];
    }

    /** @param class-string<DiInterface> $containerClass */
    #[DataProvider('containers')]
    public function testTypedSharedServicesRetainIdentityThroughAliases(string $containerClass): void
    {
        $di = new $containerClass();
        $calls = 0;
        $di->setShared('config', function () use (&$calls): Config {
            ++$calls;

            return new Config(['label' => 'original']);
        });
        $di->setAlias('config', ['applicationConfig', 'settingsConfig']);
        $di->setAlias('applicationConfig', 'nestedConfig');

        $config = $di->getConfig();
        $this->assertSame($config, $di->getConfig('nestedConfig'));
        $this->assertSame($config, $di->getShared('settingsConfig'));
        $this->assertSame($config, $di->getShared('config'));
        $this->assertTrue($di->has('nestedConfig'));
        $this->assertSame(1, $calls);

        $di->set('transient', function (): \stdClass {
            return new \stdClass();
        });
        $this->assertNotSame($di->get('transient'), $di->get('transient'));
    }

    public function testSharedFalsyValuesAreCachedWithoutRepeatedResolution(): void
    {
        foreach ([false, 0, ''] as $value) {
            $di = new Di();
            $calls = 0;
            $di->setShared('value', function () use (&$calls, $value): mixed {
                ++$calls;

                return $value;
            });
            $di->setAlias('value', 'alias');

            $this->assertSame($value, $di->getShared('value'));
            $this->assertSame($value, $di->getShared('alias'));
            $this->assertSame($value, $di->get('value'));
            $this->assertSame(1, $calls);
        }
    }

    public function testFactoryFiltersRemainLazySharedAndAllowCoreProviderRegistration(): void
    {
        foreach ([FactoryDefault::class, Cli::class] as $containerClass) {
            $di = new $containerClass();
            $this->assertFalse($di->getService('filter')->isResolved());
            $native = $di->getShared('filter');
            $this->assertSame('value', $native->sanitize(' value ', 'trim'));
            $this->assertSame($native, $di->getShared('filter'));

            // Providers register before default services are resolved during bootstrap.
            $di = new $containerClass();
            $di->setShared('config', new Config());
            (new ServiceProvider($di))->register($di);
            $core = $di->getShared('filter');
            $this->assertInstanceOf(Filter::class, $core);
            $this->assertNotSame($native, $core);
            $this->assertSame('value', $core->sanitize(' value ', 'trim'));
            $this->assertSame('{"ok":true}', $core->sanitize('{"ok":true}', Filter::FILTER_JSON));
        }
    }

    public function testConfiguredArrayCallablesWorkAlongsideNativeAndCoreFilters(): void
    {
        $di = new Di();
        $di->setShared('config', new Config(['filters' => ['date' => [\DateTimeImmutable::class, 'createFromFormat']]]));
        (new ServiceProvider($di))->register($di);
        $filter = $di->getShared('filter');
        $date = $filter->sanitize('!Y-m-d', ['date' => ['2026-10-15']]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertSame('2026-10-15 00:00:00', $date->format('Y-m-d H:i:s'));

        $filter->set('format', [$date, 'format']);
        $this->assertSame('2026-10-15', $filter->sanitize('Y-m-d', 'format'));
        $this->assertSame('value', $filter->sanitize(' value ', 'trim'));
        $this->assertSame('{"ok":true}', $filter->sanitize('{"ok":true}', Filter::FILTER_JSON));
    }

    public function testNativeContainerResolvesDefinitionsAndAliasesForCoreServices(): void
    {
        $container = new Container();
        $container->set('config', Config::class);
        $container->set('factory', static fn (): Config => new Config(['label' => 'factory']));
        $provided = new Config(['label' => 'provided']);
        $container->set('provided', $provided);
        $container->setAlias('config', 'alias')->setAlias('alias', 'nested');

        $config = $container->get('config');
        $this->assertInstanceOf(Config::class, $config);
        $this->assertSame($config, $container->get('nested'));
        $this->assertTrue($container->has('nested'));
        $this->assertNotSame($config, $container->new('nested'));
        $this->assertSame('factory', $container->get('factory')->get('label'));
        $this->assertSame($provided, $container->get('provided'));

        $this->expectException(CircularAliasFound::class);
        $container->setAlias('nested', 'config');
    }

    public function testContainerExtendsAliasesAndFreezesResolvedDefinitions(): void
    {
        $container = new Container();
        $definition = $container->set('config', Config::class);
        $container->setAlias('config', 'alias');
        $container->extend('alias', static function (Config $config): Config {
            $config->set('extended', true);

            return $config;
        });

        $this->assertTrue($container->get('config')->get('extended'));
        $this->assertTrue($definition->isFrozen());
        $this->expectException(FrozenDefinition::class);
        $definition->addTag('after-resolution');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSettingsRetainFalsyOverridesAndResetToNativeValues(): void
    {
        Settings::reset();
        $escapeIdentifiers = Settings::get('db.escape_identifiers');
        $cacheLevel = Settings::get('orm.resultset_prefetch_records');
        $nativeIni = ini_get('phalcon.db.escape_identifiers');

        try {
            $this->assertIsBool($escapeIdentifiers);
            $this->assertIsInt($cacheLevel);
            Settings::set('db.escape_identifiers', false);
            Settings::set('orm.resultset_prefetch_records', 0);
            $this->assertFalse(Settings::get('db.escape_identifiers'));
            $this->assertSame(0, Settings::get('orm.resultset_prefetch_records'));
            $this->assertSame($nativeIni, ini_get('phalcon.db.escape_identifiers'));
            Settings::set('unknown.setting', true);
            $this->assertNull(Settings::get('unknown.setting'));
        } finally {
            Settings::reset();
        }

        $this->assertSame($escapeIdentifiers, Settings::get('db.escape_identifiers'));
        $this->assertSame($cacheLevel, Settings::get('orm.resultset_prefetch_records'));
    }
}
