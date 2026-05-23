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
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;

trait MaximumAction
{
    use AbstractInjectable;
    use AbstractQuery;
    use AbstractRestResponse;
    
    /**
     * Maximum value of a column
     * Alias for maximumAction
     * @link maximumAction()
     */
    public function maxAction(): ResponseInterface
    {
        return $this->maximumAction();
    }
    
    /**
     * Maximum value of a column
     */
    public function maximumAction(): ResponseInterface
    {
        $this->view->setVar('maximum', $this->maximum());
        return $this->setRestResponse(true);
    }
}
