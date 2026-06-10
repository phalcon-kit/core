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
use Phalcon\Mvc\ModelInterface;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Restful;

final class FindActionControllerDouble extends Restful
{
    public CountActionViewDouble $view;

    /**
     * @var array<string, mixed>
     */
    public array $unitParams = [];

    public ?ResultsetInterface $findResult = null;

    /**
     * @var array<int, mixed>
     */
    public array $findWithResult = [];

    /**
     * @var array{with: array<string|int, mixed>|null, find: array<string|int, mixed>|null}|null
     */
    public ?array $findWithArguments = null;

    /**
     * @var array<string|int, mixed>|null
     */
    public ?array $preparedFindWithFind = null;

    public ?ModelInterface $findFirstWithResult = null;

    /**
     * @var array{with: array<string|int, mixed>|null, find: array<string|int, mixed>|null}|null
     */
    public ?array $findFirstWithArguments = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $exposedData = [];

    /**
     * @var list<ResultsetInterface|int|false>
     */
    public array $countResults = [];

    /**
     * @var list<array<string|int, mixed>>
     */
    public array $countFinds = [];

    public mixed $restResponse = null;

    public bool $findCalled = false;

    public bool $findWithCalled = false;

    public bool $findFirstWithCalled = false;

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
     * Return the configured resultset for normal list queries.
     */
    public function find(?array $find = null): ResultsetInterface
    {
        $this->findCalled = true;

        return $this->findResult ?? throw new \LogicException('No find result configured.');
    }

    /**
     * Return the configured eager-loaded list result.
     */
    public function findWith(?array $with = null, ?array $find = null): array
    {
        $this->findWithCalled = true;
        $this->findWithArguments = [
            'with' => $with,
            'find' => $find,
        ];
        $this->preparedFindWithFind = $find ?? $this->prepareFind();

        return $this->findWithResult;
    }

    /**
     * Return the configured eager-loaded first-record result.
     */
    public function findFirstWith(?array $with = null, ?array $find = null): ?ModelInterface
    {
        $this->findFirstWithCalled = true;
        $this->findFirstWithArguments = [
            'with' => $with,
            'find' => $find,
        ];

        return $this->findFirstWithResult;
    }

    /**
     * Return the next configured count result and record the prepared find.
     *
     * @param array<string|int, mixed>|null $find
     */
    public function count(?array $find = null): ResultsetInterface|int|false
    {
        $preparedFind = $find ?? $this->prepareFind();
        $this->countFinds[] = $this->prepareCountFind($this->getCalculationFind($preparedFind));

        return array_shift($this->countResults) ?? false;
    }

    /**
     * Return deterministic exposed data without depending on model doubles.
     */
    public function listExpose(mixed $items, ?array $expose = null): array
    {
        return $this->exposedData;
    }

    /**
     * Return deterministic exposed first-record data.
     */
    public function expose(mixed $item, ?array $expose = null): array
    {
        return $this->exposedData[0] ?? [];
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
     * Set list-action count fields from a plain list for focused tests.
     *
     * An empty list intentionally keeps the real null-policy behavior so tests
     * can assert unrestricted list counts without controller boilerplate.
     *
     * @param list<string> $countFields Count field constants enabled by the
     *     test case.
     */
    public function setUnitFindActionCountFields(array $countFields): void
    {
        $this->setFindActionCountFields(
            $countFields === []
                ? null
                : new Collection($countFields, false)
        );
    }
}
