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
use PhalconKit\Modules\Api\Controllers\TableController;
use PhalconKit\Models\Table;

/**
 * Default permission fragment for table metadata resources.
 *
 * Users can read table metadata while administrators can manage table records.
 * The management behavior skips identity and soft-delete filters because table
 * metadata is framework-owned administration data.
 */
class TableConfig extends Config
{
    /**
     * Merge the table permission fragment with caller-provided config.
     *
     * @param array<string, mixed> $data Permission overrides or extensions.
     * @param bool $insensitive Whether config keys should be case-insensitive.
     */
    public function __construct(array $data = [], bool $insensitive = true)
    {
        $data = $this->internalMergeAppend([
            'permissions' => [
                'features' => [
                    'manageTableList' => [
                        'components' => [
                            TableController::class => ['*'],
                            Table::class => ['*'],
                        ],
                        'behaviors' => [
                            TableController::class => [
                                SkipIdentityCondition::class,
                                SkipSoftDeleteCondition::class,
                            ],
                        ],
                    ],
                    'viewTableList' => [
                        'components' => [
                            TableController::class => ['get', 'get-all'],
                            Table::class => ['find', 'findFirst'],
                        ],
                    ],
                ],
                'roles' => [
                    'user' => [
                        'features' => [
                            'viewTableList',
                        ],
                    ],
                    'admin' => [
                        'features' => [
                            'manageTableList',
                        ],
                    ],
                ],
            ],
        ], $data);
        
        parent::__construct($data, $insensitive);
    }
}
