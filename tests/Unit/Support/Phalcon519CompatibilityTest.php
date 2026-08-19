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

use Phalcon\Contracts\Events\EventsAware;
use Phalcon\DataMapper\Pdo\Connection;
use Phalcon\DataMapper\Pdo\Connection\AbstractConnection;
use Phalcon\DataMapper\Pdo\Connection\ConnectionInterface;
use Phalcon\DataMapper\Pdo\Connection\Decorated;
use Phalcon\DataMapper\Pdo\ConnectionLocator;
use Phalcon\DataMapper\Pdo\Events as DataMapperEvents;
use Phalcon\DataMapper\Pdo\Exception\OperationCancelled;
use Phalcon\Events\Manager;
use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Callback;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\StringLength\Max;
use Phalcon\Filter\Validation\Validator\StringLength\Min;
use Phalcon\Html\Escaper;
use Phalcon\Html\Helper\Input\Checkbox;
use Phalcon\Html\Helper\Input\Generic;
use Phalcon\Html\Helper\Input\Radio;
use Phalcon\Image\Adapter\Imagick as PhalconImagick;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PhalconKit\Tests\Unit\Db\Fixtures\FakePdo;

#[CoversNothing]
class Phalcon519CompatibilityTest extends TestCase
{
    public function testCallbackKeepsClosureScopeAndPassesValidator(): void
    {
        $owner = new class {
            private bool $called = false;

            public function callback(): \Closure
            {
                return function (array $data, Callback $validator): bool {
                    $this->called = $data['field'] === 'value';
                    $validator->setTemplate('Callback supplied message');

                    return false;
                };
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $validation = new Validation();
        $validation->add('field', new Callback(['callback' => $owner->callback()]));
        $messages = $validation->validate(['field' => 'value']);

        $this->assertTrue($owner->wasCalled());
        $this->assertCount(1, $messages);
        $this->assertSame('Callback supplied message', $messages->current()->getMessage());
    }

    public function testStringLengthDefaultsAndAliasesUseTheExpectedBoundaries(): void
    {
        $this->assertValidationMessageCount(new Min(['min' => 2]), 'ab', 0);
        $this->assertValidationMessageCount(new Max(['max' => 2]), 'ab', 0);
        $this->assertValidationMessageCount(new Min(['min' => 2, 'includedMinimum' => false]), 'ab', 1);
        $this->assertValidationMessageCount(new Max(['max' => 2, 'includedMaximum' => false]), 'ab', 1);

        $validator = new StringLength([
            'min' => 2,
            'max' => 4,
            'includedMinimum' => false,
            'includedMaximum' => true,
            'messageMinimum' => 'minimum boundary',
            'messageMaximum' => 'maximum boundary',
        ]);

        $this->assertSame('minimum boundary', $this->validateStringLength($validator, 'a'));
        $this->assertSame('minimum boundary', $this->validateStringLength($validator, 'ab'));
        $this->assertNull($this->validateStringLength($validator, 'abcd'));
        $this->assertSame('maximum boundary', $this->validateStringLength($validator, 'abcde'));
    }

    public function testDataMapperLifecycleEventsAndCancellation(): void
    {
        $manager = new Manager();
        $seen = [];
        $manager->attach(
            DataMapperEvents::BEFORE_PERFORM,
            static function ($event, $source, array $data) use (&$seen): void {
                $seen[] = [$event->getType(), $source::class, $data];
            }
        );
        $manager->attach(
            DataMapperEvents::AFTER_PERFORM,
            static function ($event, $source, array $data) use (&$seen): void {
                $seen[] = [$event->getType(), $source::class, $data];
            }
        );

        $connection = new Decorated(new FakePdo());
        $connection->setEventsManager($manager);
        $statement = $connection->perform('SELECT :id', ['id' => 7]);

        $this->assertTrue($statement->executed);
        $this->assertSame(
            [
                ['beforePerform', Decorated::class, ['statement' => 'SELECT :id', 'values' => ['id' => 7]]],
                ['afterPerform', Decorated::class, ['statement' => 'SELECT :id', 'values' => ['id' => 7]]],
            ],
            $seen
        );

        $cancellingManager = new Manager();
        $cancellingManager->attach(
            DataMapperEvents::BEFORE_PERFORM,
            static function ($event): bool {
                $event->stop();

                return false;
            }
        );
        $connection->setEventsManager($cancellingManager);

        $this->expectException(OperationCancelled::class);
        $this->expectExceptionMessage(DataMapperEvents::BEFORE_PERFORM);
        $connection->perform('SELECT 1');
    }

    public function testDataMapperEventsManagerPropagatesThroughTheLocator(): void
    {
        $manager = new Manager();
        $master = new Decorated(new FakePdo());
        $read = new Decorated(new FakePdo());
        $locator = new ConnectionLocator($master);
        $locator->setRead('replica', static fn(): Decorated => $read);
        $locator->setEventsManager($manager);

        $this->assertInstanceOf(EventsAware::class, $master);
        $this->assertInstanceOf(EventsAware::class, $locator);
        $this->assertTrue(is_subclass_of(AbstractConnection::class, EventsAware::class));
        $this->assertTrue(is_subclass_of(Connection::class, EventsAware::class));
        $this->assertFalse(is_subclass_of(ConnectionInterface::class, EventsAware::class));
        $this->assertSame($manager, $locator->getEventsManager());
        $this->assertSame($manager, $locator->getMaster()->getEventsManager());
        $this->assertSame($manager, $locator->getRead('replica')->getEventsManager());
    }

    public function testInputHelpersCanBeBuiltWithoutADoctype(): void
    {
        $escaper = new Escaper();

        $this->assertSame(
            '<input type="email" id="choice" name="choice" value="1">',
            (string) (new Generic($escaper, null, 'email'))('choice', '1')
        );
        $this->assertSame(
            '<input type="checkbox" id="choice" name="choice" value="1">',
            (string) (new Checkbox($escaper))('choice', '1')
        );
        $this->assertSame(
            '<input type="radio" id="choice" name="choice" value="1">',
            (string) (new Radio($escaper))('choice', '1')
        );
    }

    public function testImagickOpacityAndAnimatedGifOperations(): void
    {
        if (!class_exists(\Imagick::class)) {
            $this->markTestSkipped('Imagick extension is not available.');
        }

        $baseFile = $this->temporaryFile('phalcon-519-base-');
        $watermarkFile = $this->temporaryFile('phalcon-519-watermark-');
        $gifFile = $this->temporaryFile('phalcon-519-animation-');
        $savedGifFile = $this->temporaryFile('phalcon-519-saved-animation-');

        try {
            $this->writeSolidImage($baseFile, '#ff0000');
            $this->writeSolidImage($watermarkFile, '#0000ff');

            $base = new PhalconImagick($baseFile);
            $watermark = new PhalconImagick($watermarkFile);
            $rendered = new \Imagick();
            $rendered->readImageBlob($base->watermark($watermark, 0, 0, 50)->render('png'));
            $color = $rendered->getImagePixelColor(0, 0)->getColor();

            $this->assertGreaterThan(0, $color['r']);
            $this->assertLessThan(255, $color['r']);
            $this->assertGreaterThan(0, $color['b']);
            $this->assertLessThan(255, $color['b']);

            $this->writeAnimatedGif($gifFile);
            $animation = new PhalconImagick($gifFile);
            $this->assertSame(5, $animation->getWidth());
            $this->assertSame(4, $animation->getHeight());

            $renderedAnimation = new \Imagick();
            $renderedAnimation->readImageBlob($animation->reflection(1, 50)->render('gif'));
            $this->assertSame(2, $renderedAnimation->getNumberImages());
            foreach ($renderedAnimation as $frame) {
                $this->assertSame(5, $frame->getImageWidth());
                $this->assertSame(5, $frame->getImageHeight());
            }

            $animation->save($savedGifFile);
            $savedAnimation = new \Imagick($savedGifFile);
            $this->assertSame(2, $savedAnimation->getNumberImages());
        } finally {
            foreach ([$baseFile, $watermarkFile, $gifFile, $savedGifFile] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    public function testImagickBackgroundTextAndSharpenKeepSubHundredAmounts(): void
    {
        if (!class_exists(\Imagick::class)) {
            $this->markTestSkipped('Imagick extension is not available.');
        }

        $transparentFile = $this->temporaryFile('phalcon-519-transparent-');
        $textFile = $this->temporaryFile('phalcon-519-text-');
        $sharpFile = $this->temporaryFile('phalcon-519-sharp-');
        $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

        try {
            $this->writeSolidImage($transparentFile, 'transparent');
            $background = new PhalconImagick($transparentFile);
            $renderedBackground = new \Imagick();
            $renderedBackground->readImageBlob($background->background('#00ff00', 50)->render('png'));
            $backgroundPixel = $renderedBackground->getImagePixelColor(0, 0);
            $backgroundColor = $backgroundPixel->getColor();
            $backgroundAlpha = $backgroundPixel->getColorValue(\Imagick::COLOR_ALPHA);

            $this->assertGreaterThan(0, $backgroundColor['g']);
            $this->assertGreaterThan(0.0, $backgroundAlpha);
            $this->assertLessThan(1.0, $backgroundAlpha);

            $this->assertFileExists($fontFile, 'The CI image must provide the DejaVu Sans font fixture.');
            $this->writeSolidImage($textFile, '#ffffff', 80, 30);
            $text = new PhalconImagick($textFile);
            $textBefore = $text->render('png');
            $textAfter = $text->text('Phalcon', 2, 2, 50, '#000000', 14, $fontFile)->render('png');
            $this->assertNotSame(hash('sha256', $textBefore), hash('sha256', $textAfter));

            $this->writeSharpenFixture($sharpFile);
            $sharp = new PhalconImagick($sharpFile);
            $sharpBefore = $sharp->render('png');
            $sharpAfter = $sharp->sharpen(50)->render('png');
            $this->assertNotSame(hash('sha256', $sharpBefore), hash('sha256', $sharpAfter));
        } finally {
            foreach ([$transparentFile, $textFile, $sharpFile] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function assertValidationMessageCount(object $validator, string $value, int $expected): void
    {
        $validation = new Validation();
        $validation->add('field', $validator);

        $this->assertCount($expected, $validation->validate(['field' => $value]));
    }

    private function validateStringLength(StringLength $validator, string $value): ?string
    {
        $validation = new Validation();
        $validation->add('field', $validator);
        $messages = $validation->validate(['field' => $value]);

        return $messages->count() === 0 ? null : (string) $messages->current()->getMessage();
    }

    private function temporaryFile(string $prefix): string
    {
        $file = tempnam(sys_get_temp_dir(), $prefix);

        if ($file === false) {
            $this->fail(sprintf('Could not create temporary file with prefix "%s".', $prefix));
        }

        return $file;
    }

    private function writeSolidImage(string $file, string $color, int $width = 4, int $height = 4): void
    {
        $image = new \Imagick();
        $image->newImage($width, $height, new \ImagickPixel($color));
        $image->setImageFormat('png');
        $image->writeImage($file);
    }

    private function writeSharpenFixture(string $file): void
    {
        $image = new \Imagick();
        $image->newImage(9, 9, new \ImagickPixel('#808080'));
        $image->setImagePixelColor(4, 4, new \ImagickPixel('#707070'));
        $image->setImagePixelColor(4, 5, new \ImagickPixel('#909090'));
        $image->setImageFormat('png');
        $image->writeImage($file);
    }

    private function writeAnimatedGif(string $file): void
    {
        $animation = new \Imagick();

        foreach ([['#ff0000', 0, 0], ['#0000ff', 2, 1]] as [$color, $x, $y]) {
            $frame = new \Imagick();
            $frame->newImage(3, 2, new \ImagickPixel($color));
            $frame->setImageFormat('gif');
            $frame->setImageDelay(5);
            $frame->setImagePage(5, 4, $x, $y);
            $animation->addImage($frame);
        }

        $animation->writeImages($file, true);
    }
}
