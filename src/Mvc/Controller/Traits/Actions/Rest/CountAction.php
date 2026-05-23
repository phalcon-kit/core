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

trait CountAction
{
    use AbstractInjectable;
    use AbstractQuery;
    use AbstractRestResponse;
    
    /**
     * Return the count for the current REST query.
     *
     * The response variable is named `count`. When the underlying query uses a
     * group clause, native Phalcon may return grouped count rows instead of a
     * scalar total; callers should treat this action as a thin REST wrapper
     * around the controller query contract.
     */
    public function countAction(): ResponseInterface
    {
        $this->view->setVar('count', $this->count());
        return $this->setRestResponse(true);
    }
}
