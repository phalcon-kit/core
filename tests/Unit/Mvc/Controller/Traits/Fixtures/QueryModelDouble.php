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

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Mvc\Model\Interfaces\EagerLoadInterface;

class QueryModelDouble extends Model implements EagerLoadInterface
{
    /** @var array<string, mixed> */
    public static array $calls = [];

    public static ?ResultsetInterface $resultset = null;

    public static ?ModelInterface $first = null;

    /** @var array<string, mixed> */
    public static array $aggregateResults = [];

    /** @var array<string, mixed> */
    public array $assignedData = [];

    /** @var array<int, string>|null */
    public ?array $assignedWhiteList = null;

    /** @var array<string, string>|null */
    public ?array $assignedColumnMap = null;

    public bool $saveResult = true;

    /** @var array<int, mixed> */
    public array $messages = [];

    /** @var array<int, array> */
    public array $loadedWith = [];

    public ?ModelInterface $loadedModel = null;

    public static function reset(): void
    {
        self::$calls = [];
        self::$resultset = null;
        self::$first = null;
        self::$aggregateResults = [];
    }

    public static function find($parameters = null): ResultsetInterface
    {
        self::$calls['find'] = $parameters;

        return self::$resultset ?? throw new \LogicException('No resultset configured.');
    }

    public static function findFirst($parameters = null)
    {
        self::$calls['findFirst'] = $parameters;

        return self::$first;
    }

    public static function average(array $parameters = [])
    {
        self::$calls['average'] = $parameters;

        return self::$aggregateResults['average'] ?? false;
    }

    public static function count($parameters = null)
    {
        self::$calls['count'] = $parameters;

        return self::$aggregateResults['count'] ?? false;
    }

    public static function sum($parameters = null)
    {
        self::$calls['sum'] = $parameters;

        return self::$aggregateResults['sum'] ?? false;
    }

    public static function maximum($parameters = null)
    {
        self::$calls['maximum'] = $parameters;

        return self::$aggregateResults['maximum'] ?? false;
    }

    public static function minimum($parameters = null)
    {
        self::$calls['minimum'] = $parameters;

        return self::$aggregateResults['minimum'] ?? false;
    }

    public static function findWith(array ...$arguments): array
    {
        self::$calls['findWith'] = $arguments;

        return ['with' => $arguments];
    }

    public static function findFirstWith(array ...$arguments): ?ModelInterface
    {
        self::$calls['findFirstWith'] = $arguments;

        return self::$first;
    }

    public static function with(array ...$arguments): array
    {
        self::$calls['with'] = $arguments;

        return ['with' => $arguments];
    }

    public static function firstWith(array ...$arguments): ?ModelInterface
    {
        self::$calls['firstWith'] = $arguments;

        return self::$first;
    }

    public function load(array ...$arguments): ?ModelInterface
    {
        $this->loadedWith = $arguments;

        return $this->loadedModel ?? $this;
    }

    public static function getParametersFromArguments(array &$arguments): mixed
    {
        return $arguments;
    }

    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        $this->assignedData = $data;
        $this->assignedWhiteList = $whiteList;
        $this->assignedColumnMap = $dataColumnMap;

        return $this;
    }

    public function save(): bool
    {
        return $this->saveResult;
    }

    public function getMessages($filter = null): array
    {
        return $this->messages;
    }
}
