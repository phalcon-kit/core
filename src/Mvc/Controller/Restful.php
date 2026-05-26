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

namespace PhalconKit\Mvc\Controller;

use PhalconKit\Mvc\Controller\Traits\Actions\RestActions;
use PhalconKit\Mvc\Controller\Traits\Export;
use PhalconKit\Mvc\Controller\Traits\Expose;
use PhalconKit\Mvc\Controller\Traits\Model;
use PhalconKit\Mvc\Controller\Traits\Query;

class Restful extends Rest
{
    use RestActions;
    use Export;
    use Expose;
    use Model;
    use Query;
    
    /**
     * Initialize the model-backed REST controller.
     *
     * The query initializer prepares filters, joins, conditions, bind values,
     * pagination, and the final `find` policy used by the standard REST actions.
     * The action initializer prepares response-shaping policies that are not part
     * of the database query itself, such as optional count-action metadata,
     * embedded list counts, and distinct-value fields.
     *
     * Concrete API controllers that override `initialize()` should call
     * `parent::initialize()` unless they intentionally replace the full REST
     * setup lifecycle.
     *
     * @psalm-suppress MissingReturnType Keep the framework base method untyped
     *     in the 2.x line so existing application controllers that override
     *     Phalcon's initialize hook without a return type do not break.
     */
    public function initialize()
    {
        $this->initializeQuery();
        $this->initializeRestActions();
    }
}
