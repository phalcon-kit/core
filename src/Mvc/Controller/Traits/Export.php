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

namespace PhalconKit\Mvc\Controller\Traits;

use Exception;
use League\Csv\Bom;
use League\Csv\CannotInsertRecord;
use League\Csv\CharsetConverter;
use League\Csv\InvalidArgument;
use League\Csv\Writer;
use Phalcon\Http\ResponseInterface;
use Shuchkin\SimpleXLSXGen;
use Spatie\ArrayToXml\ArrayToXml;
use PhalconKit\Support\Helper;
use PhalconKit\Support\Slug;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;

/**
 * Provides some utility methods to export data
 */
trait Export
{
    use AbstractParams;
    use AbstractModel;
    
    /**
     * Get the content type based on the given parameters.
     *
     * @param array|null $params Optional. The parameters to determine the content type. If not provided, it will use the default parameters.
     * @return string The content type. Possible values: "json", "csv", "xlsx".
     * @throws Exception When an unsupported content type is provided.
     */
    public function getContentType(?array $params = null): string
    {
        $params ??= $this->getParams();
        $contentType = strtolower($params['contentType'] ?? $params['content-type'] ?? $this->request->getContentType() ?? '');
        
        switch ($contentType) {
            case 'xml':
            case 'text/xml':
            case 'application/xml':
                return 'xml';
            
            case 'json':
            case 'text/json':
            case 'application/json':
                return 'json';
            
            case 'csv':
            case 'text/csv':
                return 'csv';
            
            case 'xlsx':
            case 'application/xlsx':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                return 'xlsx';
        }
        
        throw new Exception('`' . $contentType . '` is not supported.', 400);
    }
    
    /**
     * Returns the filename for the exported file.
     *
     * The filename is generated based on the model class name, with any
     * namespaces replaced by slashes, and then slugified. It is then
     * prepended with the current date in the 'Y-m-d' format.
     *
     * @return string The generated filename for the exported file.
     */
    public function getFilename(): string
    {
        $suffix = ' List (' . date('Y-m-d') . ')';
        $modelName = $this->getModelName() ?? '';
        return ucfirst(Slug::generate(basename(str_replace('\\', '/', $modelName)))) . $suffix;
    }
    
    /**
     * Retrieves the columns from the given list of data.
     *
     * @param array $list The list of data to extract columns from.
     *
     * @return array An associative array containing the export columns as keys.
     */
    public function getExportColumns(array $list): array
    {
        $columns = [];
        foreach ($list as $row) {
            if (is_array($row)) {
                foreach (array_keys($row) as $key) {
                    $columns[$key] = true;
                }
            }
        }
        return array_keys($columns);
    }
    
    /**
     * Exports the given list to a specified file in the specified format.
     *
     * @param array $list The list of data to export.
     * @param string|null $filename The filename of the exported file. If not provided, the default filename will be used.
     * @param string|null $contentType The content type of the exported file. If not provided, the default content type will be used.
     * @param array|null $params Additional parameters for the export process. If not provided, the default parameters will be used.
     *
     * @return ResponseInterface Returns true if the export was successful, otherwise false.
     *
     * @throws Exception Thrown if the specified content type is not supported.
     */
    public function export(array $list = [], ?string $filename = null, ?string $contentType = null, ?array $params = null): ResponseInterface
    {
        $params ??= $this->getParams();
        $contentType ??= $this->getContentType();
        $filename ??= $this->getFilename();
        
        return match ($contentType) {
            'json' => $this->exportJson($list, $filename),
            'xml' => $this->exportXml($list, $filename),
            'csv' => $this->exportCsv($list, $filename, $params),
            'xlsx' => $this->exportExcel($list, $filename),
            default => throw new Exception('Failed to export `' . $this->getModelName() . '` using unsupported content-type `' . $contentType . '`', 400)
        };
    }
    
    /**
     * Exports the given list to an XML file with the specified filename.
     *
     * @param array $list The list of data to export.
     * @param string|null $filename The filename of the exported XML file. If not provided, a default filename will be used.
     *
     * @return ResponseInterface
     */
    public function exportXml(array $list, ?string $filename = null, ?array $params = null): ResponseInterface
    {
        $params ??= $this->getParams();
        $filename ??= $this->getFilename();
        
        $rootElement = $params['rootElement'] ?? '';
        $replaceSpacesByUnderScoresInKeyNames = $params['replaceSpacesByUnderScoresInKeyNames'] ?? true;
        $xmlEncoding = $params['xmlEncoding'] ?? null;
        $xmlVersion = $params['xmlVersion'] ?? '1.0';
        $domProperties = $params['domProperties'] ?? [];
        $xmlStandalone = $params['xmlStandalone'] ?? null;
        $addXmlDeclaration = $params['addXmlDeclaration'] ?? true;
        $options = $params['options'] ?? ['convertNullToXsiNil' => false];
        
        $result = ArrayToXml::convert(
            $list,
            $rootElement,
            $replaceSpacesByUnderScoresInKeyNames,
            $xmlEncoding,
            $xmlVersion,
            $domProperties,
            $xmlStandalone,
            $addXmlDeclaration,
            $options,
        );
        
        $this->response->setContent($result);
        $this->response->setContentType('application/xml');
        $this->response->setHeader('Content-disposition', 'attachment; filename="' . addslashes($filename) . '.xml"');
        
        return $this->response;
    }
    
    /**
     * Export data as JSON file for download.
     *
     * @param mixed $list The data to be exported as JSON. Can be an array, object, or any serializable data type.
     * @param string|null $filename The name of the exported file. If not provided, the default filename will be used.
     * @param int $flags Optional JSON encoding options. Default is JSON_PRETTY_PRINT.
     * @param int $depth Optional maximum depth of recursion. Default is 2048.
     *
     * @return ResponseInterface
     */
    public function exportJson(mixed $list, ?string $filename = null, int $flags = JSON_PRETTY_PRINT, int $depth = 2048): ResponseInterface
    {
        $filename ??= $this->getFilename();

//        $this->response->setJsonContent($list); // bug with phalcon, avoid
        $this->response->setContent(json_encode($list, $flags, $depth) ?: '[]');
        $this->response->setContentType('application/json');
        $this->response->setHeader('Content-disposition', 'attachment; filename="' . addslashes($filename) . '.json"');
        
        return $this->response;
    }
    
    /**
     * Export data as an Excel spreadsheet
     *
     * @param array $list The data to be exported
     * @param string|null $filename The desired filename for the exported file (optional)
     *
     * @return ResponseInterface
     */
    public function exportExcel(array $list, ?string $filename = null, bool $forceRawValue = true): ResponseInterface
    {
        $filename ??= $this->getFilename();
        $columns = $this->getExportColumns($list);
        
        $export = [];
        $export [] = $columns;
        
        foreach ($list as $record) {
            $row = [];
            foreach ($columns as $column) {
                $value = $record[$column] ?? '';
                
                if ($value === '') {
                    $row[$column] = '';
                    continue;
                }
                
                // Remove non-printable (except new lines)
                $value = Helper::removeNonPrintable($value, '[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]');
                
                // Normalize line breaks to "\n" for consistency
                $value = Helper::normalizeLineBreaks($value);
                
                // Remove leading and trailing whitespace
                $value = trim($value);
                
                // Decode HTML entities to characters
                $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                
                // Enforce and sanitize UTF-8 encoding (except new lines)
                $value = Helper::sanitizeUTF8($value, '[^\x09\x0A\x0D\x20-\x7E\xA0-\xFF]');
                
                // Escape special characters if forcing raw value
                if ($forceRawValue && isset($value[0]) && in_array($value[0], ['=', '+', '-', '@'])) {
                    $value = "\t" . $value;
                }
                
                // Assign value to row with forced raw value prefix if necessary
                $row[$column] = ($forceRawValue ? "\0" : '') . $value;
            }
            $export [] = array_values($row);
        }

        $xlsx = SimpleXLSXGen::fromArray($export);

        $binary = (string)$xlsx;
        $this->response->setContent($binary);
        $this->response->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'UTF-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . addslashes($filename) . '.xlsx"');
        $this->response->setHeader('Content-Length', (string) strlen($binary));
        $this->response->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');

        return $this->response;
//        return $xlsx->downloadAs($filename . '.xlsx');
    }
    
    /**
     * @return ResponseInterface
     * @throws InvalidArgument
     * @throws CannotInsertRecord
     * @throws \League\Csv\Exception
     */
    public function exportCsv(array $list, ?string $filename = null, ?array $params = null): ResponseInterface
    {
        $filename ??= $this->getFilename();
        $params ??= $this->getParams();
        $columns = $this->getExportColumns($list);
        
        // Get CSV custom request parameters
        $mode = $params['mode'] ?? null;
        $delimiter = $params['delimiter'] ?? null;
        $enclosure = $params['enclosure'] ?? null;
        $endOfLine = $params['endOfLine'] ?? null;
        $escape = $params['escape'] ?? null;
        $outputBOM = $params['outputBOM'] ?? null;
        $skipIncludeBOM = $params['skipIncludeBOM'] ?? false;
        $necessaryEnclosure = $params['necessaryEnclosure'] ?? false;
        $keepEndOfLines = $params['keepEndOfLines'] ?? false;

        $csv = Writer::from('php://memory');
        $csv->setEscape('\\');
        
        // CSV - MS Excel on MacOS
        if ($mode === 'mac') {
            $csv->setOutputBOM(Bom::Utf16Le); // utf-16
            $csv->setDelimiter("\t"); // tabs separated
            $csv->setEndOfLine("\r\n"); // end of lines
            CharsetConverter::addTo($csv, 'UTF-8', 'UTF-16');
        }
        
        // CSV - MS Excel on Windows
        else {
            $csv->setOutputBOM(Bom::Utf8); // utf-8
            $csv->setDelimiter(','); // comma separated
            $csv->setEndOfLine("\r\n"); // end of lines
            CharsetConverter::addTo($csv, 'UTF-8', 'UTF-8');
        }
        
        // relax enclosure
        if ($necessaryEnclosure) {
            $csv->necessaryEnclosure();
        }
        // force enclosure
        else {
            $csv->forceEnclosure();
        }
        // set enclosure
        if (isset($enclosure)) {
            $csv->setEnclosure($enclosure);
        }
        // set output bom
        if (isset($outputBOM)) {
            $csv->setOutputBOM($outputBOM);
        }
        // set delimiter
        if (isset($delimiter)) {
            $csv->setDelimiter($delimiter);
        }
        // send end of line
        if (isset($endOfLine)) {
            $csv->setEndOfLine($endOfLine);
        }
        // set escape
        if (isset($escape)) {
            $csv->setEscape($escape);
        }
        // skip include bom
        if ($skipIncludeBOM) {
            $csv->skipInputBOM();
        }
        // include bom
        else {
            $csv->includeInputBOM();
        }
        
        // Headers
        $csv->insertOne($columns);
        
        foreach ($list as $row) {
            $outputRow = [];
            foreach ($columns as $column) {
                $outputRow[$column] = $row[$column] ?? '';
                
                // sometimes excel can't process the cells multiple lines correctly when loading csv
                // this is why we remove the new lines by default, user can choose to keep them using $keepEndOfLines
                if (!$keepEndOfLines && is_string($outputRow[$column])) {
                    $outputRow[$column] = trim(preg_replace('/\s+/', ' ', $outputRow[$column]) ?? '');
                }
            }
            $csv->insertOne($outputRow);
        }
        
        $this->response->setContent((string)$csv);
        $this->response->setContentType('text/csv');
        $this->response->setHeader('Content-disposition', 'attachment; filename="' . addslashes($filename) . '.csv"');
        
        return $this->response;
//        $csv->output($filename . '.csv');
//        return true;
    }
}
