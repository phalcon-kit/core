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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use Phalcon\Mvc\Model;

final class ModelColumnMappedModel extends Model
{
    /**
     * @return array<string, string>
     */
    public function columnMap(): array
    {
        return [
            'id' => 'id',
            'tenant' => 'tenantId',
            'created_at' => 'createdAt',
        ];
    }
}
