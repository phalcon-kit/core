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

namespace PhalconKit\Bootstrap\Permissions;

use PhalconKit\Config\Config;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipIdentityCondition;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipSoftDeleteCondition;
use PhalconKit\Modules\Api\Controllers\ColumnController;
use PhalconKit\Models\Column;

/**
 * Default permission fragment for column metadata resources.
 *
 * The fragment grants regular users read access to the column API/model and
 * grants administrators full management access with identity and soft-delete
 * query guards skipped for metadata administration screens.
 */
class ColumnConfig extends Config
{
    /**
     * Merge the column permission fragment with caller-provided config.
     *
     * @param array<string, mixed> $data Permission overrides or extensions.
     * @param bool $insensitive Whether config keys should be case-insensitive.
     */
    public function __construct(array $data = [], bool $insensitive = true)
    {
        $data = $this->internalMergeAppend([
            'permissions' => [
                'features' => [
                    'manageColumnList' => [
                        'components' => [
                            ColumnController::class => ['*'],
                            Column::class => ['*'],
                        ],
                        'behaviors' => [
                            ColumnController::class => [
                                SkipIdentityCondition::class,
                                SkipSoftDeleteCondition::class,
                            ],
                        ],
                    ],
                    'viewColumnList' => [
                        'components' => [
                            ColumnController::class => ['get', 'get-all'],
                            Column::class => ['find', 'findFirst'],
                        ],
                    ],
                ],
                'roles' => [
                    'user' => [
                        'features' => [
                            'viewColumnList',
                        ],
                    ],
                    'admin' => [
                        'features' => [
                            'manageColumnList',
                        ],
                    ],
                ],
            ],
        ], $data);
        
        parent::__construct($data, $insensitive);
    }
}
