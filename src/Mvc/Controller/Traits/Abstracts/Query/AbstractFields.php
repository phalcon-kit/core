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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts\Query;

use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractExposeFields;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractFilterFields;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractMapFields;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractSaveFields;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractSearchFields;

/**
 * Abstract contract that groups all REST field-policy collections.
 */
trait AbstractFields
{
    use AbstractExposeFields;
    use AbstractFilterFields;
    use AbstractMapFields;
    use AbstractSaveFields;
    use AbstractSearchFields;
    
    /**
     * Initialize expose, filter, map, save, and search field policies.
     */
    abstract public function initializeFields(): void;
}
