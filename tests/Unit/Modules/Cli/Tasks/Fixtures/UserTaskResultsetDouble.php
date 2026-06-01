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

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks\Fixtures;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\ResultsetInterface;

final class UserTaskResultsetDouble implements \IteratorAggregate, ResultsetInterface
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
        $row = $this->rows[array_key_last($this->rows) ?? 0] ?? null;
        return $row instanceof ModelInterface ? $row : null;
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
        return $this->rows;
    }

    public function update($data, ?\Closure $conditionCallback = null): bool
    {
        return true;
    }
}
