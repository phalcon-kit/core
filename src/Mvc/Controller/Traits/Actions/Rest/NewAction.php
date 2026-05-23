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
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractFields;

trait NewAction
{
    use AbstractExpose;
    use AbstractInjectable;
    use AbstractModel;
    use AbstractParams;
    use AbstractRestResponse;
    use AbstractFields;
    
    /**
     * Build and expose a new unsaved model instance.
     *
     * Request parameters are assigned through the configured save/map fields so
     * clients can inspect default values and server-side assignment behavior
     * before submitting a create request.
     */
    public function newAction(): ResponseInterface
    {
        $entity = $this->loadModel();
        
        $params = $this->getParams();
        $saveFields = $this->getSaveFields()?->toArray();
        $mapFields = $this->getMapFields()?->toArray();
        $entity->assign($params, $saveFields, $mapFields);
        
        $this->view->setVar('data', $this->expose($entity));
        return $this->setRestResponse(true);
    }
}
