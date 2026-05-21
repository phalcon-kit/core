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

class FakePdo extends \PDO
{
    public function __construct(private bool $throwOnExecute = false)
    {
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        return FakePdoStatement::create($query, $this->throwOnExecute);
    }
}
