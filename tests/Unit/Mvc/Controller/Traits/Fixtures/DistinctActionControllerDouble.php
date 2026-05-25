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
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Support\Collection;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Controller\Restful;

final class DistinctActionControllerDouble extends Restful
{
    public DistinctActionViewDouble $view;

    /**
     * @var array<string, mixed>
     */
    public array $params = [];

    /**
     * @var list<ResultsetInterface>
     */
    public array $findResults = [];

    /**
     * @var list<array<string|int, mixed>|null>
     */
    public array $findFinds = [];

    public mixed $restResponse = null;

    public ?int $restErrorCode = null;

    public mixed $restErrorResponse = null;

    /**
     * Disable normal query initialization for this action-focused double.
     */
    public function initialize(): void
    {
    }

    /**
     * Return a test request parameter without requiring a real HTTP request.
     */
    public function getParam(
        string $key,
        array|string|null $filters = null,
        mixed $default = null,
        ?array $params = null
    ): mixed {
        $params ??= $this->params;

        return $params[$key] ?? $default;
    }

    /**
     * Return the next configured find result and record the requested find.
     *
     * @param array<string|int, mixed>|null $find
     */
    public function find(?array $find = null): ResultsetInterface
    {
        $this->findFinds[] = $find;
        $result = array_shift($this->findResults);

        if (!$result instanceof ResultsetInterface) {
            throw new LogicException('Distinct action test did not configure a resultset.');
        }

        return $result;
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

        return new Response();
    }

    /**
     * Capture REST validation errors without requiring a real response service.
     */
    public function setRestErrorResponse(int $code = 400, ?string $status = null, mixed $response = null): ResponseInterface
    {
        $this->restErrorCode = $code;
        $this->restErrorResponse = $response;

        return new Response();
    }

    /**
     * Set distinct action fields from a plain array for focused tests.
     *
     * @param array<string|int, mixed> $fields Distinct field policy accepted by
     *     the action.
     */
    public function setUnitDistinctActionFields(array $fields): void
    {
        $this->setDistinctActionFields(
            $fields === []
                ? null
                : new Collection($fields, false)
        );
    }
}
