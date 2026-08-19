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

namespace PhalconKit\Db;

class Column extends \Phalcon\Db\Column
{
    // Boolean
    public const int YES = 1;
    public const int NO = 0;
    
    // TinyInt
    public const int MIN_UNSIGNED_TINYINT = 0;
    public const int MAX_UNSIGNED_TINYINT = 255;
    public const int MIN_SIGNED_TINYINT = -128;
    public const int MAX_SIGNED_TINYINT = 127;
    
    // SmallInt
    public const int MIN_UNSIGNED_SMALLINT = 0;
    public const int MAX_UNSIGNED_SMALLINT = 65535;
    public const int MIN_SIGNED_SMALLINT = -32768;
    public const int MAX_SIGNED_SMALLINT = 32767;
    
    // MediumInt
    public const int MIN_UNSIGNED_MEDIUMINT = 0;
    public const int MAX_UNSIGNED_MEDIUMINT = 16777215;
    public const int MIN_SIGNED_MEDIUMINT = -8388608;
    public const int MAX_SIGNED_MEDIUMINT = 8388607;
    
    // Int
    public const int MIN_UNSIGNED_INT = 0;
    public const int MAX_UNSIGNED_INT = 4294967295;
    public const int MIN_SIGNED_INT = -2147483648;
    public const int MAX_SIGNED_INT = 2147483647;
    
    // BigInt (using bcmath)
    public const string MIN_UNSIGNED_BIGINT = '0';
    public const string MAX_UNSIGNED_BIGINT = '18446744073709551615';
    public const string MIN_SIGNED_BIGINT = '-9223372036854775808';
    public const string MAX_SIGNED_BIGINT = '9223372036854775807';
    
    // Float
    public const float MIN_SIGNED_FLOAT = PHP_FLOAT_MIN;
    public const float MAX_SIGNED_FLOAT = -PHP_FLOAT_MIN;
    public const float MIN_UNSIGNED_FLOAT = PHP_FLOAT_MIN;
    public const float MAX_UNSIGNED_FLOAT = PHP_FLOAT_MAX;
    
    // Double
    public const float MIN_SIGNED_DOUBLE = -PHP_FLOAT_MAX;
    public const float MAX_SIGNED_DOUBLE = -PHP_FLOAT_MIN;
    public const float MIN_UNSIGNED_DOUBLE = PHP_FLOAT_MIN;
    public const float MAX_UNSIGNED_DOUBLE = PHP_FLOAT_MAX;
    
    // Decimal
    public const int MAX_DECIMAL_DIGIT = 65;
    
    // DateTime
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const string DATETIME_MIN = '1000-01-01 00:00:00';
    public const string DATETIME_MAX = '9999-12-31 23:59:59';
    
    // Date
    public const string DATE_FORMAT = 'Y-m-d';
    public const string DATE_MIN = '1000-01-01';
    public const string DATE_MAX = '9999-12-31';
    
    // Timestamp
    public const string TIMESTAMP_FORMAT = 'Y-m-d H:i:s';
    public const string TIMESTAMP_MIN = '1970-01-01 00:00:01';
    public const string TIMESTAMP_MAX = '2038-01-19 03:14:07';
    
    // Year
    public const int YEAR_MIN = 1901;
    public const int YEAR_MAX = 2155;
    
    // Char
    public const int CHAR_MIN_LENGTH = 0;
    public const int CHAR_MAX_LENGTH = 255;
    
    // VarChar
    public const int VARCHAR_MIN_LENGTH = 0;
    public const int VARCHAR_MAX_LENGTH = 65535;
    
    // Binary
    public const int BINARY_MIN_BYTES = 0;
    public const int BINARY_MAX_BYTES = 255;
    
    // VarBinary
    public const int VARBINARY_MIN_BYTES = 0;
    public const int VARBINARY_MAX_BYTES = 65535;
    
    // Blob
    public const int TINYBLOB_MIN_LENGTH = 0;
    public const int TINYBLOB_MAX_LENGTH = 255;
    public const int BLOB_MIN_LENGTH = 0;
    public const int BLOB_MAX_LENGTH = 65535;
    public const int MEDIUMBLOB_MIN_LENGTH = 0;
    public const int MEDIUMBLOB_MAX_LENGTH = 16777215;
    public const int LONGBLOB_MIN_LENGTH = 0;
    public const int LONGBLOB_MAX_LENGTH = 4294967295;
    
    // Text
    public const int TINYTEXT_MIN_LENGTH = 0;
    public const int TINYTEXT_MAX_LENGTH = 255;
    public const int TEXT_MIN_LENGTH = 0;
    public const int TEXT_MAX_LENGTH = 65535;
    public const int MEDIUMTEXT_MIN_LENGTH = 0;
    public const int MEDIUMTEXT_MAX_LENGTH = 16777215;
    public const int LONGTEXT_MIN_LENGTH = 0;
    public const int LONGTEXT_MAX_LENGTH = 4294967295;
}
