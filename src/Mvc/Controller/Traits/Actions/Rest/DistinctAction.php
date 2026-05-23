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
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;

trait DistinctAction
{
    use AbstractInjectable;
    use AbstractModel;
    use AbstractRestResponse;
    
    /**
     * Placeholder for a future distinct-values endpoint.
     *
     * Distinct values need a supported response shape, allowed-field rules, and
     * permission semantics before this trait can expose production behavior.
     * Until then the action returns the standard REST error response.
     */
    public function distinctAction(): ResponseInterface
    {
        // Placeholder endpoint: return a clear REST error until distinct
        // response shape, field validation, and permission semantics are
        // promoted to a supported controller contract.
        return $this->setRestErrorResponse();
    }
}
