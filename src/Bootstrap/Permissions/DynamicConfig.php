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
use PhalconKit\Mvc\Model\Dynamic;

/**
 * Default permission fragment for dynamic-model access.
 *
 * Dynamic models can target runtime-selected tables, so the default config
 * keeps the surface small: users can read dynamic records and administrators
 * can manage them. Applications should merge stricter feature/role rules when
 * dynamic access is exposed to end users.
 */
class DynamicConfig extends Config
{
    /**
     * Merge the dynamic-model permission fragment with caller-provided config.
     *
     * @param array<string, mixed> $data Permission overrides or extensions.
     * @param bool $insensitive Whether config keys should be case-insensitive.
     */
    public function __construct(array $data = [], bool $insensitive = true)
    {
        $data = $this->internalMergeAppend([
            'permissions' => [
                'features' => [
                    'manageDynamicList' => [
                        'components' => [
                            Dynamic::class => ['*'],
                        ],
                        'behaviors' => [
                        ],
                    ],
                    'viewDynamicList' => [
                        'components' => [
                            Dynamic::class => ['find', 'findFirst'],
                        ],
                    ],
                ],
                'roles' => [
                    'user' => [
                        'features' => [
                            'viewDynamicList',
                        ],
                    ],
                    'admin' => [
                        'features' => [
                            'manageDynamicList',
                        ],
                    ],
                ],
            ],
        ], $data);
        
        parent::__construct($data, $insensitive);
    }
}
