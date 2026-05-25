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

namespace PhalconKit\Mvc\Controller\Traits\Actions;

use PhalconKit\Mvc\Controller\Traits\Actions\Rest\AverageAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\CountAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\DeleteAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\DistinctAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\ExportAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\FindAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\FindFirstAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\IndexAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\MaximumAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\MinimumAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\NewAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\ReorderAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\RestoreAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\SaveAction;
use PhalconKit\Mvc\Controller\Traits\Actions\Rest\SumAction;

trait RestActions
{
    use AverageAction;
    use CountAction;
    use DeleteAction;
    use DistinctAction;
    use ExportAction;
    use FindAction;
    use FindFirstAction;
    use IndexAction;
    use MaximumAction;
    use MinimumAction;
    use NewAction;
    use ReorderAction;
    use RestoreAction;
    use SaveAction;
    use SumAction;

    /**
     * Initialize action-level REST configuration.
     *
     * Query configuration lives in {@see \PhalconKit\Mvc\Controller\Traits\Query::initializeQuery()},
     * while action traits can expose smaller policy sets that affect only their
     * response behavior. Count and distinct action policies are initialized
     * here so controllers can customize action metadata through the same
     * lifecycle used by the rest of the REST API layer.
     */
    public function initializeRestActions(): void
    {
        $this->initializeCountActionResponseFields();
        $this->initializeDistinctActionFields();
    }
}
