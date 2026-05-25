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
     * Legacy short alias for `maximumAction()`.
     */
    public function maxAction(): ResponseInterface
    {
        return $this->maximumAction();
    }
    
    /**
     * Return the maximum value for the configured aggregate column.
     *
     * The response variable is named `maximum`. Query preparation is delegated
     * to the shared query trait so REST filters and policy constraints are
     * applied consistently.
     */
    public function maximumAction(): ResponseInterface
    {
        $this->setRestViewVar(self::REST_VIEW_MAXIMUM, $this->maximum());
        return $this->setRestResponse(true);
    }
}
