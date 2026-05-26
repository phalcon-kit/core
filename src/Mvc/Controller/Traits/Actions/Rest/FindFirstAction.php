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

use Phalcon\Filter\Exception as FilterException;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExpose;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;

trait FindFirstAction
{
    use AbstractExpose;
    use AbstractQuery;
    use AbstractInjectable;
    use AbstractRestResponse;
    
    /**
     * Legacy alias for `findFirstAction()`.
     *
     * @deprecated since PhalconKit 1.0, use findFirstAction() instead.
     */
    public function getAction(): ResponseInterface
    {
        return $this->findFirstAction();
    }
    
    /**
     * Legacy alias for `findFirstWithAction()`.
     *
     * @deprecated since PhalconKit 1.0, use findFirstWithAction() instead.
     */
    public function getWithAction(): ResponseInterface
    {
        return $this->findFirstWithAction();
    }
    
    /**
     * Find, expose, and return the first record matching the prepared query.
     *
     * The action returns 404 when no entity matches. On success, `data` holds
     * the exposed model payload.
     */
    public function findFirstAction(): ResponseInterface
    {
        $result = $this->findFirst();
        
        if (!$result) {
            return $this->setRestErrorResponse(404);
        }
        
        $this->setRestViewVar(self::REST_VIEW_DATA, $this->expose($result));
        return $this->setRestResponse(true);
    }
    
    /**
     * Find the first matching record with configured eager-loaded relations.
     *
     * The action returns 404 when no entity matches. On success, `data` holds
     * the exposed model payload. If the client sends no `with` parameter, the
     * controller's default eager-load graph is used. If the client sends
     * `with`, only the requested, controller-approved subset is loaded.
     *
     * @throws FilterException When request parameter filtering fails.
     * @throws HttpException When a requested relationship is not allowed.
     */
    public function findFirstWithAction(): ResponseInterface
    {
        $result = $this->findFirstWith($this->getRequestedWith());
        
        if (!$result) {
            return $this->setRestErrorResponse(404);
        }
        
        $this->setRestViewVar(self::REST_VIEW_DATA, $this->expose($result));
        return $this->setRestResponse(true);
    }
}
