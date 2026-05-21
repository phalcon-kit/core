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

namespace PhalconKit\Tests\Unit\Db\Fixtures;

class FakePdoStatement extends \PDOStatement
{
    public array $boundValues = [];

    public bool $executed = false;

    private bool $throwOnExecute = false;

    public static function create(string $query, bool $throwOnExecute = false): self
    {
        $statement = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        assert($statement instanceof self);

        $queryString = (new \ReflectionClass(\PDOStatement::class))->getProperty('queryString');
        $queryString->setValue($statement, $query);

        $statement->throwOnExecute = $throwOnExecute;

        return $statement;
    }

    public function bindValue(string|int $param, mixed $value, int $type = \PDO::PARAM_STR): bool
    {
        $this->boundValues[$param] = [$value, $type];

        return true;
    }

    public function execute(?array $params = null): bool
    {
        if ($this->throwOnExecute) {
            throw new \RuntimeException('execute failed', 9);
        }

        $this->executed = true;

        return true;
    }
}
