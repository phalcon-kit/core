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
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;

trait AverageAction
{
    use AbstractInjectable;
    use AbstractQuery;
    use AbstractRestResponse;
    
    /**
     * Return the average value for the configured aggregate column.
     *
     * The query state is prepared by the shared query trait, so filters,
     * identity conditions, permissions, joins, and request parameters are
     * applied consistently with other REST aggregate actions.
     */
    public function averageAction(): ResponseInterface
    {
        $this->view->setVar('average', $this->average());
        return $this->setRestResponse(true);
    }
}
