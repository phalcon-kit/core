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

use Phalcon\Dispatcher\Exception as DispatcherException;
use Phalcon\Filter\Exception as FilterException;
use Phalcon\Filter\Filter;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;

trait IndexAction
{
    use AbstractInjectable;
    use AbstractParams;
    use AbstractRestResponse;
    
    /**
     * Forward an index request to the REST action that matches the HTTP method.
     *
     * POST/PUT/PATCH requests are forwarded to `save`, DELETE requests to
     * `delete`, and GET requests to either `find` or `findFirst` depending on
     * whether an integer `id` parameter is present.
     *
     * @throws DispatcherException When request forwarding fails.
     * @throws FilterException When request parameter filtering fails.
     */
    public function indexAction(): ResponseInterface
    {
        $this->restForwarding();
        $ret = $this->dispatcher->getReturnedValue();
        return $ret instanceof ResponseInterface ? $ret : $this->setRestResponse($ret);
    }
    
    /**
     * Execute the method/id based REST forwarding decision.
     *
     * Returns true when a forward was performed and false when the request
     * method is not handled by the generic index endpoint.
     *
     * @throws DispatcherException When request forwarding fails.
     * @throws FilterException When request parameter filtering fails.
     */
    protected function restForwarding(): bool
    {
        $id = $this->getParam('id', [Filter::FILTER_INT]);
        if ($this->request->isPost() || $this->request->isPut() || $this->request->isPatch()) {
            $this->dispatcher->forward(['action' => 'save']);
            return true;
        }
        else if ($this->request->isDelete()) {
            $this->dispatcher->forward(['action' => 'delete']);
            return true;
        }
        else if ($this->request->isGet()) {
            $this->dispatcher->forward(['action' => is_null($id) ? 'find' : 'findFirst']);
            return true;
        }
        return false;
    }
}
