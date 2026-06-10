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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits;

use PhalconKit\Mvc\Controller\Traits\Interfaces\RestResponseInterface;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\RestResponseControllerDouble;
use ReflectionClass;

class RestResponseTest extends AbstractUnit
{
    public function testRestResponseUsesNamedEnvelopeAndViewFields(): void
    {
        $controller = $this->newController();
        $controller->exposeSetRestViewVar($controller::REST_VIEW_DATA, ['id' => 123]);
        $controller->exposeSetRestViewVars([
            $controller::REST_VIEW_MESSAGES => ['saved'],
        ]);
        $controller->view->setVar($controller::REST_VIEW_INTERNAL, ['internal' => true]);

        $response = $controller->setRestResponse(true);
        $payload = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey($controller::REST_PAYLOAD_TIMESTAMP, $payload);
        $this->assertSame('OK', $payload[$controller::REST_PAYLOAD_STATUS]);
        $this->assertSame(200, $payload[$controller::REST_PAYLOAD_CODE]);
        $this->assertTrue($payload[$controller::REST_PAYLOAD_RESPONSE]);
        $this->assertSame([
            $controller::REST_VIEW_DATA => ['id' => 123],
            $controller::REST_VIEW_MESSAGES => ['saved'],
        ], $payload[$controller::REST_PAYLOAD_VIEW]);
        $this->assertArrayNotHasKey($controller::REST_PAYLOAD_DEBUG, $payload);
    }

    public function testRestViewVarsStripInternalPhalconViewState(): void
    {
        $controller = $this->newController();
        $controller->exposeSetRestViewVars([
            $controller::REST_VIEW_DATA => ['id' => 456],
            $controller::REST_VIEW_INTERNAL => ['internal' => true],
        ]);

        $this->assertSame([
            $controller::REST_VIEW_DATA => ['id' => 456],
        ], $controller->exposeRestViewVars());
    }

    public function testRestViewVarsCanReplaceExistingResponseFields(): void
    {
        $controller = $this->newController();
        $controller->exposeSetRestViewVars([
            $controller::REST_VIEW_DATA => ['id' => 789],
            $controller::REST_VIEW_MESSAGES => ['before'],
        ]);

        $controller->exposeSetRestViewVars([
            $controller::REST_VIEW_MESSAGES => ['after'],
            $controller::REST_VIEW_INTERNAL => ['internal' => true],
        ], false);

        $this->assertSame([
            $controller::REST_VIEW_MESSAGES => ['after'],
        ], $controller->exposeRestViewVars());
    }

    public function testRestResponseConstantsMatchPublicInterfaceContract(): void
    {
        $interface = new ReflectionClass(RestResponseInterface::class);
        $controller = new ReflectionClass(RestResponseControllerDouble::class);

        foreach ($interface->getConstants() as $name => $value) {
            $this->assertSame($value, $controller->getConstant($name), $name . ' differs.');
        }
    }

    private function newController(): RestResponseControllerDouble
    {
        $controller = new RestResponseControllerDouble();
        $controller->setDI($this->di);

        return $controller;
    }
}
