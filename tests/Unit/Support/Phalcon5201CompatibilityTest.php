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

use Phalcon\Annotations\Adapter\Stream as AnnotationsStream;
use Phalcon\Encryption\Crypt;
use Phalcon\Encryption\Crypt\Exception\Mismatch;
use Phalcon\Flash\Direct;
use Phalcon\Image\Adapter\Gd;
use Phalcon\Image\Exceptions\ImageTooLarge;
use Phalcon\Logger\Enum as LoggerEnum;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\Item;
use Phalcon\Session\Adapter\Stream as SessionStream;
use Phalcon\Storage\Adapter\Stream as StorageStream;
use Phalcon\Storage\SerializerFactory;
use Phalcon\Support\Debug\Dump;
use Phalcon\Translate\Adapter\NativeArray;
use Phalcon\Translate\InterpolatorFactory;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Db\Dialect\Mysql;
use PhalconKit\Filter\Filter;
use PhalconKit\Html\Escaper;
use PhalconKit\Http\Request;
use PhalconKit\Http\Response;
use PhalconKit\Mvc\Router;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Support\Fixtures\Phalcon5201WakeupProbe;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversNothing;

#[BackupGlobals(true)]
#[CoversNothing]
final class Phalcon5201CompatibilityTest extends AbstractUnit
{
    public function testHtmlAndJavaScriptEscapingRejectInjectionBoundaries(): void
    {
        $escaper = $this->di->get('escaper');
        $this->assertInstanceOf(Escaper::class, $escaper);

        $attributes = $escaper->attributes(['x onclick=alert(1) y' => true]);
        $this->assertStringNotContainsString(' onclick=', $attributes);

        $backslash = chr(92);
        $this->assertSame(
            $backslash . $backslash . '\x27;alert(1)//',
            $escaper->js($backslash . "';alert(1)//")
        );
    }

    public function testFilterDropsDangerousUrlSchemes(): void
    {
        $filter = $this->di->get('filter');
        $this->assertInstanceOf(Filter::class, $filter);

        $this->assertSame('', $filter->sanitize('javascript:alert(1)', Filter::FILTER_URL));
        $this->assertSame('', $filter->sanitize('data:text/html,<script>alert(1)</script>', Filter::FILTER_URL));
        $this->assertSame(
            'https://example.test/path',
            $filter->sanitize('https://example.test/path', Filter::FILTER_URL)
        );
    }

    public function testResponseLocalRedirectsCannotBecomeOpenRedirects(): void
    {
        $response = $this->di->get('response');
        $this->assertInstanceOf(Response::class, $response);

        $response->resetHeaders();
        $response->redirect('https://evil.example/phish');
        $this->assertStringNotContainsString(
            'evil.example',
            (string) $response->getHeaders()->get('Location')
        );

        $response->resetHeaders();
        $response->redirect('//evil.example/phish');
        $location = (string) $response->getHeaders()->get('Location');
        $this->assertStringNotContainsString('evil.example', $location);
        $this->assertStringStartsNotWith('//', $location);
    }

    public function testRequestChecksEveryConfiguredTrustedProxy(): void
    {
        $_SERVER['REMOTE_ADDR'] = '25.25.25.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8,25.25.25.1';

        $request = new Request();
        $request->setDI($this->di);
        $request->setTrustedProxies([
            '198.51.100.0/24',
            '25.25.25.0/24',
        ]);

        $this->assertSame('8.8.8.8', $request->getClientAddress(true));
    }

    public function testLoggerAndFlashEscapeUntrustedDisplayValues(): void
    {
        $formatter = new Line('%message%');
        $item = new Item(
            "hello\r\nCRITICAL forged\tkeep",
            'debug',
            LoggerEnum::DEBUG,
            new \DateTimeImmutable()
        );

        $this->assertSame('hello\x0D\x0ACRITICAL forged' . "\t" . 'keep', $formatter->format($item));

        $flash = new Direct();
        $flash->setEscaperService($this->di->get('escaper'));
        $flash->setImplicitFlush(false);
        $flash->setCssClasses(['error' => '"><script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $flash->error('hello'));
    }

    public function testDebugDumpEscapesArrayKeys(): void
    {
        $html = (new Dump())->variable(['<img src=x onerror=alert(1)>' => 'value']);

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
    }

    public function testCryptRejectsTamperedCbcCiphertextWithGenericMismatch(): void
    {
        $crypt = new Crypt();
        $crypt->setCipher('aes-256-cbc');
        $crypt->setKey('0123456789abcdef0123456789abcdef');

        $encrypted = $crypt->encrypt('a secret message that spans blocks');
        $encrypted[-1] = $encrypted[-1] ^ chr(255);

        $this->expectException(Mismatch::class);
        $this->expectExceptionMessage('Hash does not match.');
        $crypt->decrypt($encrypted);
    }

    public function testSessionAndStorageStreamPathsStayInsideTheirDirectories(): void
    {
        $directory = $this->temporaryDirectory('phalconkit-5201-stream-');
        $token = 'escape-' . bin2hex(random_bytes(8));
        $outside = dirname($directory) . DIRECTORY_SEPARATOR . $token;
        $inside = $directory . DIRECTORY_SEPARATOR . '.._' . $token;

        try {
            $session = new SessionStream(['savePath' => $directory]);
            $session->write('../' . $token, 'payload');

            $this->assertFileDoesNotExist($outside);
            $this->assertFileExists($inside);

            $storage = new StorageStream(
                new SerializerFactory(),
                ['storageDir' => $directory . DIRECTORY_SEPARATOR]
            );
            $method = new \ReflectionMethod($storage, 'getFilepath');
            $filepath = (string) $method->invoke($storage, '../../../../' . $token);

            $this->assertStringNotContainsString('../', $filepath);
            $this->assertStringStartsWith($directory, $filepath);
        }
        finally {
            if (is_file($inside)) {
                unlink($inside);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testAnnotationsStreamDoesNotWakeArbitraryObjects(): void
    {
        $directory = $this->temporaryDirectory('phalconkit-5201-annotations-');
        $payload = $directory . DIRECTORY_SEPARATOR . 'probe.php';
        file_put_contents($payload, serialize(new Phalcon5201WakeupProbe()));
        Phalcon5201WakeupProbe::$woken = false;

        try {
            $adapter = new AnnotationsStream([
                'annotationsDir' => $directory . DIRECTORY_SEPARATOR,
            ]);

            try {
                $adapter->read('probe');
            }
            catch (\Throwable) {
                // A rejected payload may violate the adapter's native return
                // contract; it must never instantiate the planted object.
            }

            $this->assertFalse(Phalcon5201WakeupProbe::$woken);
        }
        finally {
            if (is_file($payload)) {
                unlink($payload);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testDatabaseLimitAndMissingTranslationsTreatInputAsData(): void
    {
        $dialect = new Mysql();
        $this->assertSame(
            'SELECT * FROM users LIMIT 1',
            $dialect->limit('SELECT * FROM users', '1; DROP TABLE users')
        );

        $translate = new NativeArray(
            new InterpolatorFactory(),
            [
                'content' => [],
                'defaultInterpolator' => 'indexedArray',
            ]
        );
        $this->assertSame('%9$s', $translate->query('%9$s', ['only-one']));
    }

    public function testRouterKeepsStaticOverridePriority(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $router = new Router(false, new Config());
        $router->setDI($this->di);
        $router->add('/admin', ['controller' => 'public', 'action' => 'index'])->via('GET');
        $router->add('#^/adm[a-z]+$#u', ['controller' => 'generic', 'action' => 'catchAll'])->via('GET');
        $router->add('/admin', ['controller' => 'admin', 'action' => 'secureOverride'])->via('GET');
        $router->handle('/admin');

        $this->assertSame('admin', $router->getControllerName());
        $this->assertSame('secureOverride', $router->getActionName());
    }

    public function testGdRejectsImagesOverTheConfiguredPixelLimit(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $file = tempnam(sys_get_temp_dir(), 'phalconkit-5201-image-');
        if ($file === false) {
            $this->fail('Could not create the image fixture.');
        }

        $image = imagecreatetruecolor(2, 2);
        if ($image === false) {
            $this->fail('Could not create the GD image fixture.');
        }
        imagepng($image, $file);

        try {
            new Gd($file, maxPixels: 3);
            $this->fail('Expected the pixel limit to reject the image.');
        }
        catch (ImageTooLarge $exception) {
            $this->assertStringContainsString('pixel', strtolower($exception->getMessage()));
        }
        finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function temporaryDirectory(string $prefix): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(8));
        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            $this->fail(sprintf('Could not create temporary directory "%s".', $directory));
        }

        return $directory;
    }
}
