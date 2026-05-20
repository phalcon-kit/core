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

use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Support\Collection;

trait AbstractQuery
{
    abstract public function initializeFind(): void;
    
    abstract public function setFind(?Collection $find): void;
    
    abstract public function getFind(): ?Collection;
    
    abstract public function find(?array $find = null): ResultsetInterface;
    
    abstract public function findWith(?array $with = null, ?array $find = null): array;
    
    abstract public function findFirst(?array $find = null): ModelInterface|false|null;
    
    abstract public function findFirstWith(?array $with = null, ?array $find = null): ?ModelInterface;
    
    abstract public function average(?array $find = null): ResultsetInterface|float|false;
    
    abstract public function count(?array $find = null): ResultsetInterface|int|false;
    
    abstract public function sum(?array $find = null): ResultsetInterface|float|false;
    
    abstract public function maximum(?array $find = null): ResultsetInterface|float|false;
    
    abstract public function minimum(?array $find = null): ResultsetInterface|float|false;
    
    abstract protected function getCalculationFind(?array $find = null): array;
    
    abstract public function generateBindKey(string $prefix): string;
}
