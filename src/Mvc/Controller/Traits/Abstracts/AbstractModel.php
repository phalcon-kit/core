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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts;

trait AbstractModel
{
    abstract public function getModelName(): ?string;
    
    abstract public function setModelName(?string $modelName): void;
    
    abstract public function getModelNamespaces(): array;
    
    abstract public function setModelNamespaces(?array $modelNamespaces): void;
    
    abstract public function getModelNameFromController(?array $namespaces = null, string $needle = 'Models'): ?string;
    
    abstract public function getControllerName(): string;
    
    abstract public function loadModel(?string $modelName = null): \Phalcon\Mvc\ModelInterface;

    abstract public function modelHasColumn(string $column, ?string $modelName = null): bool;
    
    abstract public function appendModelName(string $field, ?string $modelName = null): string;
}
