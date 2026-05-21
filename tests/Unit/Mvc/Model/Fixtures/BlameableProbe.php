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

use PhalconKit\Mvc\Model\Behavior\Blameable;

class BlameableProbe extends Blameable
{
    public function publicNormalizeValue(mixed $value, ?int $columnType): mixed
    {
        return $this->normalizeValue($value, $columnType);
    }

    public function publicNormalizeJson(mixed $value): string
    {
        return $this->normalizeJson($value);
    }

    public function publicNormalizeArray(array $data, ?array $columnMap, array $columnTypes): array
    {
        return $this->normalizeArray($data, $columnMap, $columnTypes);
    }
}
