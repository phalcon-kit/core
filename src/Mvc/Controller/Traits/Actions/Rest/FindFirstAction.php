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

trait FindFirstAction
{
    use AbstractExpose;
    use AbstractQuery;
    use AbstractInjectable;
    use AbstractRestResponse;
    
    /**
     * Retrieving a single record
     * @link findFirstAction()
     * @deprecated use {@link findFirstAction()}
     */
    public function getAction(): ResponseInterface
    {
        return $this->findFirstAction();
    }
    
    /**
     * Retrieving a single record
     * @link findFirstWithAction()
     * @deprecated use {@link findFirstWithAction()}
     */
    public function getWithAction(): ResponseInterface
    {
        return $this->findFirstWithAction();
    }
    
    /**
     * Retrieving a single record
     */
    public function findFirstAction(): ResponseInterface
    {
        $result = $this->findFirst();
        
        if (!$result) {
            return $this->setRestErrorResponse(404);
        }
        
        $this->view->setVar('data', $this->expose($result));
        return $this->setRestResponse(true);
    }
    
    /**
     * Retrieving a single record
     */
    public function findFirstWithAction(): ResponseInterface
    {
        $result = $this->findFirstWith();
        
        if (!$result) {
            return $this->setRestErrorResponse(404);
        }
        
        $this->view->setVar('data', $this->expose($result));
        return $this->setRestResponse(true);
    }
}
