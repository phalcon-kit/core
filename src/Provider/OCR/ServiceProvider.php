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

namespace PhalconKit\Provider\OCR;

use PhalconKit\Di\DiInterface;
use thiagoalessio\TesseractOCR\TesseractOCR;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the OCR service.
 *
 * The service exposes a shared `TesseractOCR` instance for applications that
 * need text extraction from images or documents. Runtime availability still
 * depends on the underlying Tesseract binary and any language packs installed
 * on the host.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'ocr';
    
    /**
     * Register the shared `ocr` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), TesseractOCR::class);
    }
}
