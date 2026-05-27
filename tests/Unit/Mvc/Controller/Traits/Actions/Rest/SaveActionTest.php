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
use Phalcon\Messages\Message;
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

    public function testSingleSaveFailureWithHttpMessageCodePreservesStatusCode(): void
    {
        foreach ([400, 404, 409] as $statusCode) {
            $controller = $this->createController();
            $message = new Message('Save failure.', 'id', 'SaveFailure', $statusCode);

            $controller->exposeRespondFromSaveResult([
                SaveActionControllerDouble::REST_VIEW_SAVED => false,
                SaveActionControllerDouble::REST_VIEW_MESSAGES => [$message],
            ]);

            $this->assertSame($statusCode, $controller->response->getStatusCode());
            $this->assertFalse($controller->restResponse);
            $this->assertSame(
                [$message],
                $controller->view->getVar(SaveActionControllerDouble::REST_VIEW_MESSAGES)
            );
        }
    }

    public function testSingleSaveFailureScansForExplicitHttpMessageCode(): void
    {
        $controller = $this->createController();
        $validationMessage = new Message('Validation failed.', 'name', 'PresenceOf', 0);
        $conflictMessage = new Message('Save conflict.', 'id', 'Conflict', 409);

        $controller->exposeRespondFromSaveResult([
            SaveActionControllerDouble::REST_VIEW_SAVED => false,
            SaveActionControllerDouble::REST_VIEW_MESSAGES => [
                $validationMessage,
                $conflictMessage,
            ],
        ]);

        $this->assertSame(409, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);
        $this->assertSame(
            [$validationMessage, $conflictMessage],
            $controller->view->getVar(SaveActionControllerDouble::REST_VIEW_MESSAGES)
        );
    }

    public function testSingleSaveFailureWithNonHttpMessageCodeReturnsUnprocessableEntity(): void
    {
        $controller = $this->createController();

        $controller->exposeRespondFromSaveResult([
            SaveActionControllerDouble::REST_VIEW_SAVED => false,
            SaveActionControllerDouble::REST_VIEW_MESSAGES => [
                new Message('Validation failed.', 'name', 'PresenceOf', 0),
            ],
        ]);

        $this->assertSame(422, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);
    }

    public function testSingleSaveFailureWithServerErrorMessageCodeReturnsUnprocessableEntity(): void
    {
        $controller = $this->createController();

        $controller->exposeRespondFromSaveResult([
            SaveActionControllerDouble::REST_VIEW_SAVED => false,
            SaveActionControllerDouble::REST_VIEW_MESSAGES => [
                new Message('Persistence failed.', 'id', 'PersistenceFailed', 500),
            ],
        ]);

        $this->assertSame(422, $controller->response->getStatusCode());
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
