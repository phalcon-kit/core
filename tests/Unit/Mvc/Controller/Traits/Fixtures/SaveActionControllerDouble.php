<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Mvc\Controller\Restful;

final class SaveActionControllerDouble extends Restful
{
    public Response $response;

    public DistinctActionViewDouble $view;

    public mixed $restResponse = null;

    /**
     * Disable normal query initialization for this action-focused double.
     */
    public function initialize(): void
    {
    }

    /**
     * Capture the REST response body requested by the action.
     */
    public function setRestResponse(
        mixed $response = null,
        ?int $code = null,
        ?string $status = null,
        int $jsonOptions = 0,
        int $depth = 512
    ): ResponseInterface {
        $this->restResponse = $response;

        return $this->response;
    }

    /**
     * Expose save response normalization for focused status-code tests.
     *
     * @param array<string, mixed> $ret Result returned by save(), create(), or
     *     update().
     */
    public function exposeRespondFromSaveResult(array $ret): ResponseInterface
    {
        return $this->respondFromSaveResult($ret);
    }
}
