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

namespace PhalconKit\Mvc\Controller\Traits\Actions\Rest;

use Phalcon\Http\ResponseInterface;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExpose;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;

trait FindAction
{
    use AbstractExpose;
    use AbstractParams;
    use AbstractQuery;
    use AbstractInjectable;
    use AbstractRestResponse;
    
    /**
     * Legacy alias for `findAction()`.
     *
     * @deprecated since PhalconKit 1.0, use findAction() instead.
     */
    public function getAllAction(): ResponseInterface
    {
        return $this->findAction();
    }
    
    /**
     * Legacy alias for `findWithAction()`.
     *
     * @deprecated since PhalconKit 1.0, use findWithAction() instead.
     */
    public function getAllWithAction(): ResponseInterface
    {
        return $this->findWithAction();
    }
    
    /**
     * Find and expose records matching the prepared REST query.
     *
     * The `data` response variable receives the exposed result list. Query
     * preparation is delegated to the shared query trait, so filters, fields,
     * permissions, identity constraints, ordering, limits, and joins stay
     * consistent across REST list endpoints.
     */
    public function findAction(): ResponseInterface
    {
        $this->setRestViewVar(self::REST_VIEW_DATA, $this->listExpose($this->find()));
        return $this->setRestResponse(true);
    }
    
    /**
     * Find records with eager-loaded relationships and expose the result list.
     *
     * Relationships are resolved by the controller/model eager-loading
     * contract. The exposed response shape remains the same as `findAction()`,
     * with related data included where configured.
     */
    public function findWithAction(): ResponseInterface
    {
        $this->setRestViewVar(self::REST_VIEW_DATA, $this->listExpose($this->findWith()));
        return $this->setRestResponse(true);
    }
}
