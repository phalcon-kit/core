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
     * Find and expose the resultset.
     * @link findAction()
     *
     * @deprecated use {@link findAction()}
     * @return ResponseInterface The HTTP response that indicates the success of retrieving all records.
     */
    public function getAllAction(): ResponseInterface
    {
        return $this->findAction();
    }
    
    /**
     * Find with relationships and expose the resultset.
     * @link findWithAction()
     *
     * @deprecated use {@link findWithAction()}
     * @return ResponseInterface The HTTP response that contains the resultset with the relationships.
     */
    public function getAllWithAction(): ResponseInterface
    {
        return $this->findWithAction();
    }
    
    /**
     * Find and expose the resultset.
     *
     * This method finds the resultset and exposes it for further processing.
     *
     * @return ResponseInterface The HTTP response that indicates the success of finding and exposing the resultset.
     */
    public function findAction(): ResponseInterface
    {
        $this->view->setVar('data', $this->listExpose($this->find()));
        return $this->setRestResponse(true);
    }
    
    /**
     * Find with relationships and expose the resultset.
     *
     * This method finds the resultset with relationships and exposes it for further processing.
     *
     * @return ResponseInterface The HTTP response that indicates the success of finding and exposing the resultset.
     */
    public function findWithAction(): ResponseInterface
    {
        $this->view->setVar('data', $this->listExpose($this->findWith()));
        return $this->setRestResponse(true);
    }
}
