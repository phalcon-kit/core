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

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'ocr';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), TesseractOCR::class);
    }
}
