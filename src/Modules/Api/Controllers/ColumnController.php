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

namespace PhalconKit\Modules\Api\Controllers;

use PhalconKit\Modules\Api\Controller;

class ColumnController extends Controller
{
    protected ?int $limit = 100;
    protected ?int $maxLimit = 100;
    
    #[\Override]
    public function initializeFilterFields(): void
    {
        $this->setFilterFields([
            'workspaceId',
            'tableId',
        ]);
    }
}
