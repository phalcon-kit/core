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

namespace PhalconKit\Mvc\Model\Traits;

use Phalcon\Db\Column;
use Phalcon\Mvc\Model\ResultsetInterface;

/**
 * Provides small `IN (...)` helpers for models with an integer `id` column.
 *
 * The current trait intentionally exposes only `findInById()`. More generic
 * `findIn*` variants need field validation, bind-type inference, and clear
 * naming rules before they become public model API.
 */
trait FindIn
{
    public static function findInById(array $idList = []): ResultsetInterface
    {
        $castInt = function (string|int $id): int {
            return (int)$id;
        };
        
        $idList = array_unique(array_filter(array_map($castInt, $idList)));
        $idList = empty($idList) ? [null] : $idList;
        
        $bindParam = '_id' . uniqid('_') . '_';
        
        return self::find([
            '[id] in ({' . $bindParam . ':array})',
            'bind' => [$bindParam => $idList],
            'bindTypes' => [$bindParam => Column::BIND_PARAM_INT]
        ]);
    }
}
