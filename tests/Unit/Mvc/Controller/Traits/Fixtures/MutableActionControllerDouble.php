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
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Mvc\Controller\Restful;

final class MutableActionControllerDouble extends Restful
{
    public Response $response;

    public DistinctActionViewDouble $view;

    public ?ModelInterface $entity = null;

    /**
     * @var array<string, mixed>
     */
    public array $params = [];

    public mixed $restResponse = null;

    /**
     * Disable normal query initialization for this action-focused double.
     */
    public function initialize(): void
    {
    }

    /**
     * Return the configured entity without preparing a database query.
     */
    public function findFirst(?array $find = null): ModelInterface|false|null
    {
        return $this->entity;
    }

    /**
     * Return deterministic exposed data without depending on model metadata.
     */
    public function expose(mixed $item, ?array $expose = null): array
    {
        return ['entity' => true];
    }

    /**
     * Return one synthetic request parameter.
     */
    public function getParam(
        string $key,
        array|string|null $filters = null,
        mixed $default = null,
        ?array $params = null
    ): mixed {
        $params ??= $this->params;

        return array_key_exists($key, $params) ? $params[$key] : $default;
    }

    /**
     * Capture the REST response body and status requested by the action.
     */
    public function setRestResponse(
        mixed $response = null,
        ?int $code = null,
        ?string $status = null,
        int $jsonOptions = 0,
        int $depth = 512
    ): ResponseInterface {
        $this->restResponse = $response;
        $code ??= $this->response->getStatusCode() ?: 200;
        $this->response->setStatusCode($code, $status ?? '');

        return $this->response;
    }
}
