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
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;

trait DeleteAction
{
    use AbstractExpose;
    use AbstractQuery;
    use AbstractInjectable;
    use AbstractRestResponse;
    
    /**
     * Delete the first record matching the prepared REST query.
     *
     * The action returns 404 when no entity matches. On success or failure it
     * exposes the attempted entity, the boolean delete result, and model
     * messages so clients can display domain validation or delete errors.
     */
    public function deleteAction(): ResponseInterface
    {
        $entity = $this->findFirst();
        
        if (!$entity) {
            return $this->setRestErrorResponse(404);
        }
        
        $deleted = $entity->delete();
        $this->setRestViewVars([
            self::REST_VIEW_DELETED => $deleted,
            self::REST_VIEW_DATA => $this->expose($entity),
            self::REST_VIEW_MESSAGES => $entity->getMessages(),
        ]);
        
        return $this->setRestResponse($deleted);
    }
}
