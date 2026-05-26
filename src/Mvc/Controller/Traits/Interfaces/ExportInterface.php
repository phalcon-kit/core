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

namespace PhalconKit\Mvc\Controller\Traits\Interfaces;

use Phalcon\Http\ResponseInterface;
use PhalconKit\Exception\HttpException;
use PhalconKit\Exception\RuntimeException;

/**
 * Contract for REST file export helpers.
 *
 * Implementations prepare a download response for JSON, XML, CSV, or XLSX
 * output. Export methods return the response object so callers can continue
 * using the normal Phalcon response pipeline.
 */
interface ExportInterface
{
    /**
     * Resolve the requested export content type.
     *
     * @param array<string, mixed>|null $params Request/export parameters.
     *
     * @return string One of `json`, `xml`, `csv`, or `xlsx`.
     *
     * @throws HttpException When the requested content type is unsupported.
     */
    public function getContentType(?array $params = null): string;
    
    /**
     * Build the default export filename without a file extension.
     */
    public function getFilename(): string;
    
    /**
     * Determine the union of exported columns from an array payload.
     *
     * @param array<array-key, mixed> $list Rows to inspect.
     *
     * @return list<string>
     */
    public function getExportColumns(array $list): array;
    
    /**
     * Export rows using the requested or detected content type.
     *
     * @param array<array-key, mixed> $list Rows to export.
     * @param string|null $filename Filename without extension.
     * @param string|null $contentType Explicit export type.
     * @param array<array-key, mixed>|null $params Export options.
     *
     * @throws HttpException When the content type or request-controlled export
     *     options are invalid.
     * @throws RuntimeException When the selected exporter accepts its options
     *     but cannot generate the response payload.
     */
    public function export(array $list, ?string $filename = null, ?string $contentType = null, ?array $params = null): ResponseInterface;
    
    /**
     * Export rows as XML.
     *
     * @param array<array-key, mixed> $list Rows to export.
     * @param string|null $filename Filename without extension.
     * @param array<array-key, mixed>|null $params XML export options.
     */
    public function exportXml(array $list, ?string $filename = null, ?array $params = null): ResponseInterface;
    
    /**
     * Export a serializable value as JSON.
     *
     * @param mixed $list Serializable export payload.
     * @param string|null $filename Filename without extension.
     * @param int $flags `json_encode()` flags.
     * @param int $depth Maximum JSON depth.
     */
    public function exportJson(mixed $list, ?string $filename = null, int $flags = JSON_PRETTY_PRINT, int $depth = 2048): ResponseInterface;
    
    /**
     * Export rows as XLSX.
     *
     * @param array<array-key, mixed> $list Rows to export.
     * @param string|null $filename Filename without extension.
     * @param bool $forceRawValue Prefix formula-like values to reduce
     *     spreadsheet formula injection risk.
     */
    public function exportExcel(array $list, ?string $filename = null, bool $forceRawValue = true): ResponseInterface;
    
    /**
     * Export rows as CSV.
     *
     * @param array<array-key, mixed> $list Rows to export.
     * @param string|null $filename Filename without extension.
     * @param array<array-key, mixed>|null $params CSV export options.
     *
     * @throws HttpException When a CSV option has an invalid type or value.
     * @throws RuntimeException When CSV generation fails after options have
     *     been accepted.
     */
    public function exportCsv(array $list, ?string $filename = null, ?array $params = null): ResponseInterface;
}
