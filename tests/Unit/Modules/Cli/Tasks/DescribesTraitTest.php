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

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks;

use Phalcon\Db\Column;
use PhalconKit\Modules\Cli\Tasks\Traits\DescribesTrait;
use PhalconKit\Tests\Unit\AbstractUnit;

class DescribesTraitTest extends AbstractUnit
{
    public function testGetDefaultValueTreatsSqlNullSentinelAsPhpNull(): void
    {
        $describer = new class {
            use DescribesTrait;
        };

        $column = new Column('parent_id', [
            'type' => Column::TYPE_INTEGER,
            'default' => 'NULL',
            'notNull' => false,
        ]);

        $this->assertNull($describer->getDefaultValue($column));
    }
}
