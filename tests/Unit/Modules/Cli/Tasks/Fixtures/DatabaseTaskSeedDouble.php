<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks\Fixtures;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\ModelInterface;

/**
 * Record seed writes without querying a database or hashing account fields.
 */
class DatabaseTaskSeedDouble extends Model
{
    public string $label = '';

    public static array $saved = [];

    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        $this->label = $data['label'];

        return $this;
    }

    public function save(): bool
    {
        self::$saved[] = $this->label;

        return true;
    }
}
