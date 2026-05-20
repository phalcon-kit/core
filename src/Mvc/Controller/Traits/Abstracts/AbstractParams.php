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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts;

trait AbstractParams
{
    abstract public function getParam(
        string $key,
        array|string|null $filters = null,
        mixed $default = null,
        ?array $params = null
    ): mixed;
    
    /**
     * @return array<array-key, mixed>
     */
    abstract public function getParams(
        ?array $fields = null,
        bool $cached = true,
        bool $deep = true
    ): array;
}
