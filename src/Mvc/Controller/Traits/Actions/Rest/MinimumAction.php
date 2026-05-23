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

trait MinimumAction
{
    use AbstractInjectable;
    use AbstractQuery;
    use AbstractRestResponse;
    
    /**
     * Minimum value of a column
     * Alias for minimumAction
     * @link minimumAction()
     */
    public function minAction(): ResponseInterface
    {
        return $this->minimumAction();
    }
    
    /**
     * Minimum value of a column
     */
    public function minimumAction(): ResponseInterface
    {
        $this->view->setVar('minimum', $this->minimum());
        return $this->setRestResponse(true);
    }
}
