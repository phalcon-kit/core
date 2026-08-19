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

namespace PhalconKit\Tests\Unit\Db;

use Phalcon\Contracts\Db\Column as ColumnContract;
use PhalconKit\Db\Column;
use PhalconKit\Tests\Unit\AbstractUnit;

class ColumnConstantsTest extends AbstractUnit
{
    public function testColumnExtendsPhalconColumn(): void
    {
        $column = new Column('id', [
            'type' => Column::TYPE_INTEGER,
        ]);

        $this->assertInstanceOf(\Phalcon\Db\Column::class, $column);
        $this->assertInstanceOf(ColumnContract::class, $column);
    }

    public function testBooleanConstants(): void
    {
        $this->assertSame(1, Column::YES);
        $this->assertSame(0, Column::NO);
    }

    public function testIntegerBoundaryConstants(): void
    {
        $this->assertSame(0, Column::MIN_UNSIGNED_TINYINT);
        $this->assertSame(255, Column::MAX_UNSIGNED_TINYINT);
        $this->assertSame(-128, Column::MIN_SIGNED_TINYINT);
        $this->assertSame(127, Column::MAX_SIGNED_TINYINT);

        $this->assertSame(0, Column::MIN_UNSIGNED_SMALLINT);
        $this->assertSame(65535, Column::MAX_UNSIGNED_SMALLINT);
        $this->assertSame(-32768, Column::MIN_SIGNED_SMALLINT);
        $this->assertSame(32767, Column::MAX_SIGNED_SMALLINT);

        $this->assertSame(0, Column::MIN_UNSIGNED_MEDIUMINT);
        $this->assertSame(16777215, Column::MAX_UNSIGNED_MEDIUMINT);
        $this->assertSame(-8388608, Column::MIN_SIGNED_MEDIUMINT);
        $this->assertSame(8388607, Column::MAX_SIGNED_MEDIUMINT);

        $this->assertSame(0, Column::MIN_UNSIGNED_INT);
        $this->assertSame(4294967295, Column::MAX_UNSIGNED_INT);
        $this->assertSame(-2147483648, Column::MIN_SIGNED_INT);
        $this->assertSame(2147483647, Column::MAX_SIGNED_INT);
    }

    public function testBigIntegerBoundaryConstantsRemainStrings(): void
    {
        $this->assertSame('0', Column::MIN_UNSIGNED_BIGINT);
        $this->assertSame('18446744073709551615', Column::MAX_UNSIGNED_BIGINT);
        $this->assertSame('-9223372036854775808', Column::MIN_SIGNED_BIGINT);
        $this->assertSame('9223372036854775807', Column::MAX_SIGNED_BIGINT);
    }

    public function testDateAndTextBoundaryConstants(): void
    {
        $this->assertSame('Y-m-d H:i:s', Column::DATETIME_FORMAT);
        $this->assertSame('1000-01-01 00:00:00', Column::DATETIME_MIN);
        $this->assertSame('9999-12-31 23:59:59', Column::DATETIME_MAX);

        $this->assertSame('Y-m-d', Column::DATE_FORMAT);
        $this->assertSame('1000-01-01', Column::DATE_MIN);
        $this->assertSame('9999-12-31', Column::DATE_MAX);

        $this->assertSame(0, Column::TEXT_MIN_LENGTH);
        $this->assertSame(65535, Column::TEXT_MAX_LENGTH);
        $this->assertSame(16777215, Column::MEDIUMTEXT_MAX_LENGTH);
        $this->assertSame(4294967295, Column::LONGTEXT_MAX_LENGTH);
    }

    public function testDecimalTimestampYearAndBinaryBoundaryConstants(): void
    {
        $this->assertSame(65, Column::MAX_DECIMAL_DIGIT);

        $this->assertSame('Y-m-d H:i:s', Column::TIMESTAMP_FORMAT);
        $this->assertSame('1970-01-01 00:00:01', Column::TIMESTAMP_MIN);
        $this->assertSame('2038-01-19 03:14:07', Column::TIMESTAMP_MAX);

        $this->assertSame(1901, Column::YEAR_MIN);
        $this->assertSame(2155, Column::YEAR_MAX);

        $this->assertSame(0, Column::BINARY_MIN_BYTES);
        $this->assertSame(255, Column::BINARY_MAX_BYTES);
        $this->assertSame(0, Column::VARBINARY_MIN_BYTES);
        $this->assertSame(65535, Column::VARBINARY_MAX_BYTES);
    }

    public function testBlobBoundaryConstants(): void
    {
        $this->assertSame(0, Column::TINYBLOB_MIN_LENGTH);
        $this->assertSame(255, Column::TINYBLOB_MAX_LENGTH);
        $this->assertSame(0, Column::BLOB_MIN_LENGTH);
        $this->assertSame(65535, Column::BLOB_MAX_LENGTH);
        $this->assertSame(0, Column::MEDIUMBLOB_MIN_LENGTH);
        $this->assertSame(16777215, Column::MEDIUMBLOB_MAX_LENGTH);
        $this->assertSame(0, Column::LONGBLOB_MIN_LENGTH);
        $this->assertSame(4294967295, Column::LONGBLOB_MAX_LENGTH);
    }
}
