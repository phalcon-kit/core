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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Actions\Rest;

use Phalcon\Http\Response;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\DistinctActionViewDouble;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\SaveActionControllerDouble;

class SaveActionTest extends AbstractUnit
{
    public function testSingleSaveFailureWithMessagesReturnsUnprocessableEntity(): void
    {
        $controller = $this->createController();

        $controller->exposeRespondFromSaveResult([
            SaveActionControllerDouble::REST_VIEW_SAVED => false,
            SaveActionControllerDouble::REST_VIEW_MESSAGES => ['validation failed'],
        ]);

        $this->assertSame(422, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);
        $this->assertSame(
            ['validation failed'],
            $controller->view->getVar(SaveActionControllerDouble::REST_VIEW_MESSAGES)
        );
    }

    public function testSingleSaveFailureWithoutMessagesReturnsBadRequest(): void
    {
        $controller = $this->createController();

        $controller->exposeRespondFromSaveResult([
            SaveActionControllerDouble::REST_VIEW_SAVED => false,
            SaveActionControllerDouble::REST_VIEW_MESSAGES => [],
        ]);

        $this->assertSame(400, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);
    }

    public function testBatchSaveStatusSemanticsArePreserved(): void
    {
        $controller = $this->createController();

        $controller->exposeRespondFromSaveResult([
            SaveActionControllerDouble::REST_VIEW_RESULTS => [
                [SaveActionControllerDouble::REST_VIEW_SAVED => true],
                [SaveActionControllerDouble::REST_VIEW_SAVED => false],
            ],
        ]);

        $this->assertSame(207, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);

        $controller = $this->createController();

        $controller->exposeRespondFromSaveResult([
            SaveActionControllerDouble::REST_VIEW_RESULTS => [
                [SaveActionControllerDouble::REST_VIEW_SAVED => false],
            ],
        ]);

        $this->assertSame(422, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);

        $controller = $this->createController();

        $controller->exposeRespondFromSaveResult([
            SaveActionControllerDouble::REST_VIEW_RESULTS => [
                [SaveActionControllerDouble::REST_VIEW_SAVED => true],
            ],
        ]);

        $this->assertSame(200, $controller->response->getStatusCode());
        $this->assertTrue($controller->restResponse);
    }

    /**
     * Build a controller wired for save action response tests.
     */
    private function createController(): SaveActionControllerDouble
    {
        $controller = new SaveActionControllerDouble();
        $controller->response = new Response();
        $controller->view = new DistinctActionViewDouble();

        return $controller;
    }
}
