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

use Phalcon\Db\ResultInterface;

class DbResultDouble implements ResultInterface
{
    public function __construct(private readonly int $rowCount = 1)
    {
    }

    public function dataSeek(int $number)
    {
        return true;
    }

    public function execute(): bool
    {
        return true;
    }

    public function fetch()
    {
        return false;
    }

    public function fetchAll(): array
    {
        return [];
    }

    public function fetchArray()
    {
        return false;
    }

    public function getInternalResult(): \PDOStatement
    {
        throw new \RuntimeException('No PDO statement.');
    }

    public function numRows(): int
    {
        return $this->rowCount;
    }

    public function setFetchMode(int $fetchMode): bool
    {
        return true;
    }
}
