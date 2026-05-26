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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits;

use League\Csv\InvalidArgument as CsvInvalidArgument;
use Phalcon\Http\Response;
use PhalconKit\Exception\HttpException;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\ExportControllerDouble;

class ExportTest extends AbstractUnit
{
    public function testExportCsvReturnsDownloadResponse(): void
    {
        $controller = $this->createController();

        $response = $controller->exportCsv([
            [
                'name' => 'Alice',
                'note' => "Line one\nLine two",
            ],
        ], 'contacts', [
            'delimiter' => ';',
        ]);

        $this->assertSame($controller->response, $response);
        $this->assertStringContainsString('"name";"note"', $response->getContent());
        $this->assertStringContainsString('"Alice";"Line one Line two"', $response->getContent());
        $this->assertSame('text/csv', $response->getHeaders()->get('Content-Type'));
        $this->assertSame(
            'attachment; filename="contacts.csv"',
            $response->getHeaders()->get('Content-disposition')
        );
    }

    public function testExportCsvRejectsInvalidOptionTypesAsHttpBadRequest(): void
    {
        $controller = $this->createController();

        try {
            $controller->exportCsv([['name' => 'Alice']], 'contacts', [
                'delimiter' => [';'],
            ]);
        }
        catch (HttpException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertStringContainsString(
                'Invalid CSV export option "delimiter": expected string or null, got array.',
                $e->getMessage()
            );
            $this->assertNull($e->getPrevious());
            return;
        }

        $this->fail('Expected invalid CSV option type to throw an HTTP exception.');
    }

    public function testExportCsvWrapsCsvOptionExceptionsAsHttpBadRequest(): void
    {
        $controller = $this->createController();

        try {
            $controller->exportCsv([['name' => 'Alice']], 'contacts', [
                'delimiter' => 'too-long',
            ]);
        }
        catch (HttpException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertStringContainsString('Invalid CSV export option:', $e->getMessage());
            $this->assertInstanceOf(CsvInvalidArgument::class, $e->getPrevious());
            return;
        }

        $this->fail('Expected invalid League CSV option to throw an HTTP exception.');
    }

    private function createController(): ExportControllerDouble
    {
        $controller = new ExportControllerDouble();
        $controller->response = new Response();

        return $controller;
    }
}
