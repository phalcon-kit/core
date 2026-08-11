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
    private int $position = 0;

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
        if ($this->position >= $this->rowCount) {
            return false;
        }

        return ['id' => ++$this->position];
    }

    public function fetchAll(): array
    {
        // Phalcon 5.18 materializes resultsets when count() is first called
        // instead of relying on numRows(). Return the configured synthetic
        // rows so resultset-based relationship deletion tests retain their
        // intended non-empty/empty behavior.
        $rows = [];
        while (($row = $this->fetch()) !== false) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function fetchArray()
    {
        return $this->fetch();
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
