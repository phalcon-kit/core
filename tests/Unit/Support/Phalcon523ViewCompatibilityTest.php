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

use Phalcon\Events\Manager;
use Phalcon\Mvc\View\Engine\Php;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Phalcon\Mvc\View\Engine\Volt\Exceptions\TemplateFileNotFound;
use Phalcon\Mvc\View\Simple;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Di\Di;
use PhalconKit\Mvc\View;
use PhalconKit\Provider\View\ServiceProvider as ViewProvider;
use PhalconKit\Provider\Volt\ServiceProvider as VoltProvider;
use PhalconKit\Support\Env;
use PhalconKit\Tests\Unit\AbstractUnit;

/**
 * Exercise Core's view providers against Phalcon 5.23 compiler and path changes.
 *
 * Templates and compiled files stay in a per-test directory, so the shared Volt
 * service can exercise cold and cached rendering without touching application
 * caches. Windows path cases use the native platform when run on Windows.
 */
final class Phalcon523ViewCompatibilityTest extends AbstractUnit
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phalconkit-523-view-'
            . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
        mkdir($this->directory . 'views', 0777, true);
        mkdir($this->directory . 'cache', 0777, true);
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->directory) && is_dir($this->directory)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $entry) {
                    if ($entry->isDir()) {
                        rmdir($entry->getPathname());
                    } else {
                        unlink($entry->getPathname());
                    }
                }
                rmdir($this->directory);
            }
        } finally {
            parent::tearDown();
        }
    }

    public function testSharedVoltProviderKeepsInheritedBlocksAndPartialsIndependent(): void
    {
        $this->writeTemplate('layout.volt', '<main>{% block heading %}{{ partial("heading") }}'
            . '{% endblock %}{% block body %}Base{% endblock %}</main>');
        $this->writeTemplate('heading.volt', '<header>{{ title }}</header>');
        $this->writeTemplate('login.volt', '{% extends "layout.volt" %}{% block heading %}{% endblock %}'
            . '{% block body %}Login {{ name }}{% endblock %}');
        $this->writeTemplate('main.volt', '{% extends "layout.volt" %}'
            . '{% block body %}Main {{ name }}{% endblock %}');
        $this->writeTemplate('plain.volt', 'Plain {{ name }}');
        $view = $this->createView(true);
        $compiler = $this->compiler($view);

        foreach ([true, false] as $stat) {
            // Warm inherited templates with stat enabled before disabling source checks.
            $compiler->setOption('stat', $stat);
            foreach (['login', 'main', 'plain', 'main', 'login'] as $template) {
                $expected = match ($template) {
                    'login' => '<main>Login Ada</main>',
                    'main' => '<main><header>Navigation</header>Main Ada</main>',
                    'plain' => 'Plain Ada',
                };
                $actual = $this->captureOutput(static fn () => $view->partial($template, [
                    'title' => 'Navigation',
                    'name' => 'Ada',
                ]));

                $this->assertSame($expected, $actual, $template);
                $this->assertSame($compiler, $this->compiler($view));
            }
        }
    }

    public function testProviderCompilerResetsInheritanceAcrossFilesAndStrings(): void
    {
        $this->writeTemplate('layout.volt', '[{% block body %}Base{% endblock %}]');
        $child = $this->writeTemplate('child.volt', '{% extends "layout.volt" %}'
            . '{% block body %}Child{% endblock %}');
        $plain = $this->writeTemplate('plain.volt', 'Plain');
        $compiler = $this->compiler($this->createView(true, ['stat' => false]));

        $compiler->compile($plain);
        $compiler->compile($child);
        $compiler->compile($plain);
        $this->assertSame('Independent', $compiler->compileString('Independent'));
        $compiler->setOption('stat', true);
        $this->assertSame('[String child]', $compiler->compileString(
            '{% extends "layout.volt" %}{% block body %}String child{% endblock %}'
        ));
        $this->assertSame('Independent again', $compiler->compileString('Independent again'));

        $childOutput = $this->directory . 'cache/child.php';
        $plainOutput = $this->directory . 'cache/plain.php';
        $compiler->compileFile($child, $childOutput);
        $compiler->compileFile($plain, $plainOutput);

        $this->assertSame('[Child]', file_get_contents($childOutput));
        $this->assertSame('Plain', file_get_contents($plainOutput));
    }

    public function testProviderCompilerInvalidatesPathsWhenOptionsChange(): void
    {
        $template = $this->writeTemplate('RecordCard.volt', 'Card');
        $compiler = $this->compiler($this->createView(true));
        $compiler->compile($template);
        $first = $compiler->getCompiledTemplatePath();
        $compiler->compile($template);
        $this->assertSame($first, $compiler->getCompiledTemplatePath());

        $compiler->setOption('separator', '-');
        $compiler->compile($template);
        $second = $compiler->getCompiledTemplatePath();
        $compiler->setOptions(['path' => $this->directory . 'cache/', 'separator' => '+']);
        $compiler->compile($template);
        $third = $compiler->getCompiledTemplatePath();

        $this->assertNotSame($first, $second);
        $this->assertNotSame($second, $third);
        foreach ([$first, $second, $third] as $compiledPath) {
            $this->assertStringStartsWith($this->directory . 'cache/', $compiledPath);
            $this->assertSame('Card', file_get_contents($compiledPath));
        }
    }

    public function testProviderCompilerCallsConfiguredPathResolverOnEveryRender(): void
    {
        $this->writeTemplate('resolved.volt', 'Resolved {{ name }}');
        $calls = [];
        $compiledPath = $this->directory . 'cache/resolved.php';
        $view = $this->createView(true, [
            'stat' => false,
            'path' => static function (string $templatePath) use (&$calls, $compiledPath): string {
                $calls[] = $templatePath;

                return $compiledPath;
            },
        ]);

        foreach (['Ada', 'Grace'] as $name) {
            $this->assertSame('Resolved ' . $name, $this->captureOutput(
                static fn () => $view->partial('resolved', ['name' => $name])
            ));
        }

        $this->assertCount(2, $calls);
        $this->assertSame($calls[0], $calls[1]);
        $this->assertSame($compiledPath, $this->compiler($view)->getCompiledTemplatePath());
    }

    public function testProviderCompilerCanRetryATemplateCreatedAfterAnEarlierMiss(): void
    {
        $compiler = $this->compiler($this->createView(true));
        $template = $this->directory . 'views/late.volt';
        try {
            $compiler->compile($template);
            $this->fail('A missing template must fail before it is created.');
        } catch (TemplateFileNotFound) {
            $this->writeTemplate('late.volt', 'Created later');
        }

        clearstatcache(true, $template);
        $compiler->compile($template);

        $this->assertSame('Created later', file_get_contents($compiler->getCompiledTemplatePath()));
    }

    public function testProviderCompilerHonorsExtensionsAddedDuringCompilation(): void
    {
        $compiler = $this->compiler($this->createView(true));
        $extension = new class {
            public function compileStatement(array $statement): ?string
            {
                return ($statement['value'] ?? null) === 'Marker' ? 'statement|' : null;
            }

            public function resolveExpression(array $expression): ?string
            {
                return ($expression['value'] ?? null) === 'original' ? "'expression|'" : null;
            }

            public function compileFilter(string $name): ?string
            {
                return $name === 'decorate' ? "'filter|'" : null;
            }

            public function compileFunction(string $name): ?string
            {
                return $name === 'provider_value' ? "'function|'" : null;
            }
        };
        $compiler->addFunction('enable_feature', static function () use ($compiler, $extension): string {
            $compiler->addExtension($extension);

            return "''";
        });
        $compiled = $compiler->compileString('{{ enable_feature() }}Marker{{ "original" }}'
            . '{{ 1|decorate }}{{ provider_value() }}{{ "untouched" }}');
        $this->assertIsString($compiled);
        $compiledPath = $this->directory . 'cache/extension.php';
        file_put_contents($compiledPath, $compiled);

        $this->assertSame('statement|expression|filter|function|untouched', $this->captureOutput(
            static function () use ($compiledPath): void {
                require $compiledPath;
            }
        ));
    }

    public function testDefaultNativeVoltEngineStillRendersWithTheCoreViewProvider(): void
    {
        $this->writeTemplate('native.volt', 'Native {{ name }}');
        $view = $this->createView();

        $this->assertSame('Native Ada', $this->captureOutput(
            static fn () => $view->partial('native', ['name' => 'Ada'])
        ));
    }

    public function testSharedVoltProviderAcceptsTheDefaultAndConfiguredPrefix(): void
    {
        $this->writeTemplate('prefix.volt', 'Configured prefix');
        $environment = Env::$vars;
        try {
            foreach ([null, '', 'application_'] as $prefix) {
                Env::set('VOLT_PREFIX', $prefix);
                $view = $this->createView(true);

                $this->assertSame('Configured prefix', $this->captureOutput(
                    static fn () => $view->partial('prefix')
                ));
                $this->assertSame($prefix ?? '', $this->compiler($view)->getOption('prefix'));
            }
        } finally {
            Env::$vars = $environment;
        }
    }

    public function testProviderPartialsResolveParentSegmentsAndStayInsideViewsDirectory(): void
    {
        $this->writeTemplate('partials/card.phtml', 'Safe <?= $name ?>');
        $this->writeTemplate('unused/partials/card.phtml', 'Wrong child directory');
        mkdir($this->directory . 'partials');
        file_put_contents($this->directory . 'partials/card.phtml', 'Outside views directory');
        $view = $this->createView();

        foreach ($this->relativePaths() as $path) {
            $this->assertSame('Safe Ada', $this->captureOutput(
                static fn () => $view->partial($path, ['name' => 'Ada'])
            ), $path);
        }
    }

    public function testNativeSimpleViewUsesTheSameParentSegmentResolutionWithCoreDi(): void
    {
        $this->writeTemplate('partials/card.phtml', 'Safe <?= $name ?>');
        $this->writeTemplate('unused/partials/card.phtml', 'Wrong child directory');
        $view = new Simple();
        $view->setDI($this->createView()->getDI());
        $view->setViewsDir($this->directory . 'views/');

        foreach ($this->relativePaths() as $path) {
            $this->assertSame('Safe Grace', $view->render($path, ['name' => 'Grace']), $path);
        }
    }

    public function testAbsolutePathsBypassSlugLookupUsingNativePlatformRules(): void
    {
        $paths = PHP_OS === 'WINNT'
            ? ['C:\\ProjectViews\\RecordCard', 'C:/ProjectViews/RecordCard']
            : ['/ProjectViews/RecordCard', '//ProjectViews/RecordCard'];

        foreach ($paths as $path) {
            $view = new class extends View {
                public function has(string $view): bool
                {
                    throw new \LogicException('Absolute path reached slug fallback: ' . $view);
                }
            };
            $view->disable();
            $this->assertFalse($view->render($path, 'ShowCard'));
            $this->assertSame('', $view->getRender($path, 'ShowCard', [], static function (View $view): void {
                $view->disable();
            }));
        }
    }

    public function testRelativeAndAbsolutePathSamplesFollowTheCurrentPlatform(): void
    {
        $paths = [
            '/ProjectViews/RecordCard' => PHP_OS !== 'WINNT',
            'C:/ProjectViews/RecordCard' => PHP_OS === 'WINNT',
            'C:\\ProjectViews\\RecordCard' => PHP_OS === 'WINNT',
            'ProjectViews/RecordCard' => false,
            './ProjectViews/RecordCard' => false,
            '' => false,
        ];

        foreach ($paths as $path => $absolute) {
            $view = new class extends View {
                public int $lookups = 0;

                public function has(string $view): bool
                {
                    ++$this->lookups;

                    return false;
                }
            };
            $view->disable();
            $view->render($path, '');

            $this->assertSame($absolute ? 0 : 1, $view->lookups, $path);
        }
    }

    private function createView(bool $sharedVolt = false, array $voltOptions = []): View
    {
        $di = new Di();
        $di->setShared('config', new Config([
            'view' => ['engines' => ['.phtml' => Php::class, '.volt' => $sharedVolt ? 'volt' : Volt::class]],
            'volt' => array_replace([
                'path' => $this->directory . 'cache/',
                'separator' => '_',
                'stat' => true,
            ], $voltOptions),
        ]));
        $di->setShared('eventsManager', new Manager());
        (new ViewProvider($di))->register($di);
        (new VoltProvider($di))->register($di);
        $view = $di->getTyped('view', View::class);
        $view->setViewsDir($this->directory . 'views/');

        return $view;
    }

    private function compiler(View $view): Compiler
    {
        $engine = $view->getDI()->getShared('volt');
        $this->assertInstanceOf(Volt::class, $engine);

        return $engine->getCompiler();
    }

    private function writeTemplate(string $name, string $contents): string
    {
        $path = $this->directory . 'views/' . $name;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $contents);

        return $path;
    }

    /** @return list<string> */
    private function relativePaths(): array
    {
        return ['partials/card', './partials/./card', 'unused/../partials/card',
            'unused\\..\\partials\\card', '../../partials/card'];
    }

    private function captureOutput(callable $render): string
    {
        ob_start();
        try {
            $render();

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
