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

trait SumAction
{
    use AbstractInjectable;
    use AbstractQuery;
    use AbstractRestResponse;
    
    /**
     * Return the sum for the configured aggregate column.
     *
     * The response variable is named `sum`. Query preparation is delegated to
     * the shared query trait so filters, permissions, joins, and request state
     * match the other REST aggregate actions.
     */
    public function sumAction(): ResponseInterface
    {
        $this->setRestViewVar(self::REST_VIEW_SUM, $this->sum());
        return $this->setRestResponse(true);
    }
}
