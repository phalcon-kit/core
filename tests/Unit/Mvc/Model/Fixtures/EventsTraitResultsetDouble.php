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

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\ResultsetInterface;

class EventsTraitResultsetDouble implements \IteratorAggregate, ResultsetInterface
{
    /**
     * @param list<mixed> $rows Rows returned by the synthetic resultset.
     */
    public function __construct(private readonly array $rows = [])
    {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->rows);
    }

    public function delete(?\Closure $conditionCallback = null): bool
    {
        return true;
    }

    public function filter($filter): array
    {
        return [];
    }

    public function getCache(): mixed
    {
        return null;
    }

    public function getFirst(): mixed
    {
        return $this->rows[0] ?? null;
    }

    public function getHydrateMode(): int
    {
        return 0;
    }

    public function getLast(): ?ModelInterface
    {
        if ($this->rows === []) {
            return null;
        }

        $last = $this->rows[array_key_last($this->rows)];
        return $last instanceof ModelInterface ? $last : null;
    }

    public function getMessages(): array
    {
        return [];
    }

    public function getType(): int
    {
        return 0;
    }

    public function isFresh(): bool
    {
        return true;
    }

    public function setHydrateMode(int $hydrateMode): ResultsetInterface
    {
        return $this;
    }

    public function setIsFresh(bool $isFresh): ResultsetInterface
    {
        return $this;
    }

    public function toArray(): array
    {
        return array_map(static function (mixed $row): mixed {
            return is_object($row) && method_exists($row, 'toArray')
                ? $row->toArray()
                : $row;
        }, $this->rows);
    }

    public function update($data, ?\Closure $conditionCallback = null): bool
    {
        return true;
    }
}
