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
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractOrderFields;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractSaveFields;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractSearchFields;

/**
 * Abstract contract that groups all REST field-policy collections.
 *
 * These policies control which model fields can cross the public REST boundary
 * for exposure, filtering, mapping, ordering, saving, and searching. Keeping
 * the contracts grouped makes controller initialization predictable while still
 * letting each policy expose its own focused getter/setter methods.
 */
trait AbstractFields
{
    use AbstractExposeFields;
    use AbstractFilterFields;
    use AbstractMapFields;
    use AbstractOrderFields;
    use AbstractSaveFields;
    use AbstractSearchFields;
    
    /**
     * Initialize expose, filter, map, order, save, and search field policies.
     */
    abstract public function initializeFields(): void;
}
