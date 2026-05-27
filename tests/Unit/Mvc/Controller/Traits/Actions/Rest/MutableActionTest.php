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
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\MutableActionControllerDouble;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\MutableActionModelDouble;

final class MutableActionTest extends AbstractUnit
{
    public function testDeleteActionFailureUsesModelMessageStatusCode(): void
    {
        $message = new Message('Delete conflict.', 'id', 'Conflict', 409);
        $entity = new MutableActionModelDouble();
        $entity->deleteResult = false;
        $entity->messages = [$message];
        $controller = $this->createController($entity);

        $controller->deleteAction();

        $this->assertSame(409, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);
        $this->assertFalse($controller->view->getVar(MutableActionControllerDouble::REST_VIEW_DELETED));
        $this->assertSame([$message], $controller->view->getVar(MutableActionControllerDouble::REST_VIEW_MESSAGES));
    }

    public function testRestoreActionFailureWithValidationMessagesReturnsUnprocessableEntity(): void
    {
        $entity = new MutableActionModelDouble();
        $entity->restoreResult = false;
        $entity->messages = [new Message('Restore failed.', 'deleted', 'RestoreFailed')];
        $controller = $this->createController($entity);

        $controller->restoreAction();

        $this->assertSame(422, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);
        $this->assertFalse($controller->view->getVar(MutableActionControllerDouble::REST_VIEW_RESTORED));
    }

    public function testReorderActionFailureWithoutMessagesReturnsBadRequest(): void
    {
        $entity = new MutableActionModelDouble();
        $entity->reorderResult = false;
        $controller = $this->createController($entity, ['position' => 4]);

        $controller->reorderAction();

        $this->assertSame(400, $controller->response->getStatusCode());
        $this->assertFalse($controller->restResponse);
        $this->assertFalse($controller->view->getVar(MutableActionControllerDouble::REST_VIEW_REORDERED));
        $this->assertSame(4, $entity->reorderedPosition);
    }

    public function testMutableActionsReturnNotFoundWhenEntityIsMissing(): void
    {
        foreach (['deleteAction', 'restoreAction', 'reorderAction'] as $action) {
            $controller = $this->createController(null, ['position' => 1]);

            $controller->{$action}();

            $this->assertSame(404, $controller->response->getStatusCode(), $action);
            $this->assertNull($controller->restResponse, $action);
        }
    }

    public function testMutableActionSuccessResponsesRemainOk(): void
    {
        $entity = new MutableActionModelDouble();
        $controller = $this->createController($entity, ['position' => 8]);

        $controller->deleteAction();
        $this->assertSame(200, $controller->response->getStatusCode());
        $this->assertTrue($controller->restResponse);
        $this->assertTrue($controller->view->getVar(MutableActionControllerDouble::REST_VIEW_DELETED));

        $controller = $this->createController($entity, ['position' => 8]);
        $controller->restoreAction();
        $this->assertSame(200, $controller->response->getStatusCode());
        $this->assertTrue($controller->restResponse);
        $this->assertTrue($controller->view->getVar(MutableActionControllerDouble::REST_VIEW_RESTORED));

        $controller = $this->createController($entity, ['position' => 8]);
        $controller->reorderAction();
        $this->assertSame(200, $controller->response->getStatusCode());
        $this->assertTrue($controller->restResponse);
        $this->assertTrue($controller->view->getVar(MutableActionControllerDouble::REST_VIEW_REORDERED));
        $this->assertSame(8, $entity->reorderedPosition);
    }

    /**
     * Build a controller wired for mutable REST action response tests.
     *
     * @param array<string, mixed> $params Synthetic request parameters.
     */
    private function createController(
        ?MutableActionModelDouble $entity,
        array $params = []
    ): MutableActionControllerDouble {
        $controller = new MutableActionControllerDouble();
        $controller->response = new Response();
        $controller->view = new DistinctActionViewDouble();
        $controller->entity = $entity;
        $controller->params = $params;

        return $controller;
    }
}
