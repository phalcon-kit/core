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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Conditions;

use Phalcon\Db\Column;
use Phalcon\Filter\Filter;
use Phalcon\Support\Collection;

trait AbstractFilterConditions
{
    abstract public function initializeFilterConditions(): void;
    
    abstract public function setFilterConditions(?Collection $filterConditions): void;
    
    abstract public function getFilterConditions(): ?Collection;
    
    abstract public function defaultFilterCondition(?array $filters, ?array $allowedFilters, ?string $aliasContext = null, bool $or = false, int $level = 0): array|string|null;
    
    abstract public function normalizeFilterOperator(string $operator): string;
    
    abstract public function getBindTypeFromRawValue(mixed $rawValue = null): int;
}
