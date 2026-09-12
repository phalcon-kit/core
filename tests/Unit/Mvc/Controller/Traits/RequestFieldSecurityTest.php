<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits;

use Phalcon\Di\Di;
use Phalcon\Filter\FilterFactory;
use PhalconKit\Encryption\Security;
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Restful;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Exercises request compilation with the actual model qualifier, without database access. */
final class RequestFieldSecurityTest extends TestCase
{
    public static function unsafeFields(): array
    {
        $cases = [];
        foreach (['filter', 'order', 'group'] as $consumer) {
            foreach ([
                'function escape' => 'ABS(id))))) OR 1=1 OR ((((ABS(id)',
                'bracket escape' => '[id])))) OR 1=1 OR (((([id]',
                'subquery' => 'ABS((SELECT id FROM [PrivateRecord]))',
                'untrusted function' => 'SLEEP(10)',
            ] as $name => $field) {
                $cases[$consumer . ': ' . $name] = [$consumer, $field];
            }
        }
        return $cases;
    }

    #[DataProvider('unsafeFields')]
    public function testRequestsRejectExpressionsBeforeCompilingQueries(string $consumer, string $field): void
    {
        $controller = $this->controller([$consumer => [$field => 'asc']]);
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Invalid query field.');
        match ($consumer) {
            'filter' => $controller->defaultFilterCondition([['field' => $field, 'operator' => 'equals', 'value' => 0]]),
            'order' => $controller->initializeOrder(),
            'group' => $controller->initializeGroup(),
        };
    }

    public function testIdentifiersAndBoundValuesKeepTheirQueryStructure(): void
    {
        $controller = $this->controller(['order' => ['score' => 'desc'], 'group' => 'status']);
        $controller->initializeOrder();
        $controller->initializeGroup();
        self::assertSame(['score' => '[AuditRecord].[score] desc'], $controller->getOrder()->toArray());
        self::assertSame(['status' => '[AuditRecord].[status]'], $controller->getGroup()->toArray());
        $value = '0 OR 1=1';
        [$condition, $bind] = $controller->defaultFilterCondition([['field' => 'score', 'operator' => 'equals', 'value' => $value]]);
        self::assertStringNotContainsString($value, $condition);
        self::assertSame([$value], array_values($bind));
        $parsed = \Phalcon\Mvc\Model\Query\Lang::parsePHQL('SELECT * FROM [AuditRecord] WHERE [ownerId] = 42 AND (' . $condition . ')');
        self::assertIsArray($parsed);
    }

    public function testExplicitOrderMapAndControllerDefaultsStillAllowExpressions(): void
    {
        $controller = $this->controller(['order' => ['scoreRank' => 'desc']]);
        $controller->setOrderFields(['scoreRank' => 'ABS([AuditRecord].[score])']);
        $controller->initializeOrder();
        self::assertSame(['scoreRank' => 'ABS([AuditRecord].[score]) desc'], $controller->getOrder()->toArray());

        $controller = $this->controller();
        $controller->initializeOrder();
        self::assertSame(['ABS(score)' => 'ABS(score) asc'], $controller->getOrder()->toArray());
    }

    private function controller(array $params = []): Restful
    {
        $previousDi = Di::getDefault();
        $di = new \PhalconKit\Di\Di();
        $di->setShared('filter', new FilterFactory()->newInstance());
        $di->setShared('security', new Security());
        $controller = new class extends Restful {
            public array $params = [];

            public function initialize(): void
            {
            }

            public function getModelName(): ?string
            {
                return 'AuditRecord';
            }

            public function getParam(string $key, array|string|null $filters = null, mixed $default = null, ?array $params = null): mixed
            {
                return ($params ?? $this->params)[$key] ?? $default;
            }

            public function hasParam(string $key, ?array $params = null, bool $cached = true): bool
            {
                return array_key_exists($key, $params ?? $this->params);
            }

            public function initializeDefaultOrder(): void
            {
                $this->setDefaultOrder('ABS(score) asc');
            }
        };
        $controller->params = $params;
        $controller->setDI($di);
        Di::reset();
        if ($previousDi !== null) {
            Di::setDefault($previousDi);
        }
        return $controller;
    }
}
