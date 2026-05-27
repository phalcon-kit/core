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
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExpose;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;
use PhalconKit\Mvc\Model\Interfaces\SoftDeleteInterface;

trait RestoreAction
{
    use AbstractExpose;
    use AbstractInjectable;
    use AbstractQuery;
    use AbstractRestResponse;
    
    /**
     * Restore the first soft-deleted entity matching the prepared REST query.
     *
     * The action returns 404 when no entity matches. The configured model must
     * implement `SoftDeleteInterface`; on completion the response exposes the
     * attempted entity, the restore result, and model messages.
     *
     * @throws LogicException When the configured model does not support soft
     *     deletes.
     */
    public function restoreAction(): ResponseInterface
    {
        $entity = $this->findFirst();
        
        if (!$entity) {
            return $this->setRestErrorResponse(404);
        }
        
        $entity = $this->requireSoftDeleteEntity($entity);
        $restored = $entity->restore();
        $messages = $entity->getMessages();
        
        $this->setRestViewVars([
            self::REST_VIEW_RESTORED => $restored,
            self::REST_VIEW_DATA => $this->expose($entity),
            self::REST_VIEW_MESSAGES => $messages,
        ]);

        if ($restored !== true) {
            return $this->setRestActionFailureResponse($messages, $restored);
        }
        
        return $this->setRestResponse(true);
    }

    /**
     * Require the current REST entity to support soft-delete restoration.
     *
     * The action can be composed into any REST controller, but it is valid only
     * for models that expose PhalconKit's soft-delete contract. A helper keeps
     * the action body focused on the workflow while converting an invalid
     * controller/model pairing into a stable framework exception.
     *
     * @param ModelInterface $entity Entity returned by the query layer.
     *
     * @return ModelInterface&SoftDeleteInterface
     *
     * @throws LogicException When the configured model does not support soft
     *     deletes.
     */
    protected function requireSoftDeleteEntity(ModelInterface $entity): ModelInterface&SoftDeleteInterface
    {
        if ($entity instanceof SoftDeleteInterface) {
            return $entity;
        }

        throw new LogicException(sprintf(
            'Configured model "%s" must implement "%s" to use restoreAction().',
            $entity::class,
            SoftDeleteInterface::class
        ));
    }
}
