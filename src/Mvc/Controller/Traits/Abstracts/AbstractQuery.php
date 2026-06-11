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

/**
 * Abstract contract consumed by traits that delegate to the REST query layer.
 *
 * Controllers using only part of the query stack can include this trait to
 * document the methods they expect from the full {@see \PhalconKit\Mvc\Controller\Traits\Query}
 * composition without coupling to one concrete controller class.
 */
trait AbstractQuery
{
    /**
     * Initialize the prepared find-option collection.
     */
    abstract public function initializeFind(): void;
    
    /**
     * Replace the prepared find-option collection.
     */
    abstract public function setFind(array|Collection|null $find): void;
    
    /**
     * Return the prepared find-option collection.
     */
    abstract public function getFind(): ?Collection;

    /**
     * Compile the current find collection into Phalcon query options.
     *
     * @param Collection|null $find Optional collection to compile instead of
     *     the controller's current find state.
     * @param bool $ignoreKey Preserve compatibility with the concrete query
     *     compiler signature.
     *
     * @return array<string|int, mixed>
     */
    abstract public function prepareFind(?Collection $find = null, bool $ignoreKey = false): array;
    
    /**
     * Execute a model find query.
     *
     * @param array<string|int, mixed>|null $find Optional Phalcon find options.
     */
    abstract public function find(?array $find = null): ResultsetInterface;
    
    /**
     * Execute a find query and eager-load relations.
     *
     * @param array<string, mixed>|null $with Eager-load relation map.
     * @param array<string|int, mixed>|null $find Optional Phalcon find options.
     *
     * @return array<int|string, mixed>
     */
    abstract public function findWith(?array $with = null, ?array $find = null): array;
    
    /**
     * Execute a model find-first query.
     *
     * @param array<string|int, mixed>|null $find Optional Phalcon find options.
     */
    abstract public function findFirst(?array $find = null): ModelInterface|false|null;
    
    /**
     * Execute a find-first query and eager-load relations.
     *
     * @param array<string, mixed>|null $with Eager-load relation map.
     * @param array<string|int, mixed>|null $find Optional Phalcon find options.
     */
    abstract public function findFirstWith(?array $with = null, ?array $find = null): ?ModelInterface;
    
    /**
     * Execute an average aggregate query.
     *
     * @param array<string|int, mixed>|null $find Optional aggregate options.
     */
    abstract public function average(?array $find = null): ResultsetInterface|float|false;
    
    /**
     * Execute a count aggregate query.
     *
     * @param array<string|int, mixed>|null $find Optional aggregate options.
     */
    abstract public function count(?array $find = null): ResultsetInterface|int|false;
    
    /**
     * Execute a sum aggregate query.
     *
     * @param array<string|int, mixed>|null $find Optional aggregate options.
     */
    abstract public function sum(?array $find = null): ResultsetInterface|float|false;
    
    /**
     * Execute a maximum aggregate query.
     *
     * @param array<string|int, mixed>|null $find Optional aggregate options.
     */
    abstract public function maximum(?array $find = null): ResultsetInterface|float|false;
    
    /**
     * Execute a minimum aggregate query.
     *
     * @param array<string|int, mixed>|null $find Optional aggregate options.
     */
    abstract public function minimum(?array $find = null): ResultsetInterface|float|false;
    
    /**
     * Normalize find options before aggregate execution.
     *
     * @param array<string|int, mixed>|null $find Optional aggregate options.
     *
     * @return array<string|int, mixed>
     */
    abstract protected function getCalculationFind(?array $find = null): array;
    
    /**
     * Generate a collision-resistant bind key for query parameters.
     */
    abstract public function generateBindKey(string $prefix): string;
}
