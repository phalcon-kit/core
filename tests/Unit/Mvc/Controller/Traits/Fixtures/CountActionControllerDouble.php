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
use PhalconKit\Mvc\Controller\Restful;

final class CountActionControllerDouble extends Restful
{
    public CountActionViewDouble $view;

    /**
     * @var array<string, mixed>
     */
    public array $unitParams = [];

    /**
     * @var list<ResultsetInterface|int|false>
     */
    public array $countResults = [];

    /**
     * @var list<array<string|int, mixed>|null>
     */
    public array $countFinds = [];

    public mixed $restResponse = null;

    /**
     * Disable normal query initialization for this action-focused double.
     */
    public function initialize(): void
    {
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
        $params ??= $this->unitParams;

        return array_key_exists($key, $params) ? $params[$key] : $default;
    }

    /**
     * Return the next configured count result and record the requested find.
     *
     * @param array<string|int, mixed>|null $find
     */
    public function count(?array $find = null): ResultsetInterface|int|false
    {
        $this->countFinds[] = $find;

        return array_shift($this->countResults) ?? false;
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
     * Set extra count action fields from a plain list for focused tests.
     *
     * @param list<string> $responseFields Count response field constants enabled
     *     by the test case.
     */
    public function setUnitCountActionResponseFields(array $responseFields): void
    {
        $this->setCountActionResponseFields(
            $responseFields === []
                ? null
                : new Collection($responseFields, false)
        );
    }
}
