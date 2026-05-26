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

namespace PhalconKit\Mvc\Controller\Traits\Query;

use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\ExposeFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\FilterFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\MapFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\OrderFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\SaveFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\SearchFields;

/**
 * Groups REST field-policy initialization for query controllers.
 *
 * The individual subtraits own their storage and merge behavior. This trait
 * keeps the request lifecycle compact by initializing all public field
 * policies before conditions, ordering, and persistence logic consume them.
 */
trait Fields
{
    use AbstractFields;
    
    use ExposeFields;
    use FilterFields;
    use MapFields;
    use OrderFields;
    use SaveFields;
    use SearchFields;
    
    /**
     * Initialize every REST field-policy collection for the current request.
     *
     * Concrete controllers can override the specific `initialize*Fields()`
     * method they own instead of replacing this aggregate method. That keeps
     * event ordering stable while still allowing resource-specific policies.
     */
    public function initializeFields(): void
    {
        $this->initializeExposeFields();
        $this->initializeFilterFields();
        $this->initializeMapFields();
        $this->initializeOrderFields();
        $this->initializeSaveFields();
        $this->initializeSearchFields();
    }
}
