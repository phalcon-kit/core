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

use Phalcon\Mvc\ModelInterface;
use PhalconKit\Di\FactoryDefault;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Controller\Restful;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\ModelColumnAlternateModel;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\ModelColumnMappedModel;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\ModelColumnMetadataModel;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeMetaData;

class ModelTraitTest extends AbstractUnit
{
    public function testGetModelNamespacesRejectsInvalidLoaderService(): void
    {
        $controller = new class extends Restful {
            /**
             * Disable normal REST initialization for this trait-focused test.
             */
            public function initialize(): void
            {
            }
        };

        $di = new FactoryDefault();
        $di->set('loader', new \stdClass());
        $controller->setDI($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "loader" to be an instance of "Phalcon\Autoload\Loader"'
        );

        $controller->getModelNamespaces();
    }

    public function testModelHasColumnUsesCurrentControllerModelColumnMap(): void
    {
        $controller = $this->newModelColumnController(ModelColumnMappedModel::class);

        $this->assertTrue($controller->modelHasColumn('tenant'));
        $this->assertTrue($controller->modelHasColumn('tenantId'));
    }

    public function testModelHasColumnAcceptsExplicitModelName(): void
    {
        $controller = $this->newModelColumnController(ModelColumnMappedModel::class);

        $this->assertTrue($controller->modelHasColumn('workspace_uuid', ModelColumnAlternateModel::class));
        $this->assertTrue($controller->modelHasColumn('workspaceUuid', ModelColumnAlternateModel::class));
    }

    public function testModelHasColumnFallsBackToModelMetadataWhenColumnMapMethodIsMissing(): void
    {
        $controller = $this->newModelColumnController(ModelColumnMetadataModel::class);

        $this->assertTrue($controller->modelHasColumn('legacy_code'));
        $this->assertTrue($controller->modelHasColumn('legacyCode'));
    }

    public function testModelHasColumnReturnsFalseForMissingOrInvalidModel(): void
    {
        $controller = $this->newModelColumnController(null);

        $this->assertFalse($controller->modelHasColumn('tenant'));
        $this->assertFalse($controller->modelHasColumn('tenant', \stdClass::class));
    }

    public function testModelHasColumnReturnsFalseForUnknownColumn(): void
    {
        $controller = $this->newModelColumnController(ModelColumnMappedModel::class);

        $this->assertFalse($controller->modelHasColumn('missing'));
    }

    public function testModelHasColumnAcceptsRawColumnAndMappedAttributeNames(): void
    {
        $controller = $this->newModelColumnController(ModelColumnMappedModel::class);

        $this->assertTrue($controller->modelHasColumn('created_at'));
        $this->assertTrue($controller->modelHasColumn('createdAt'));
    }

    private function newModelColumnController(?string $modelName): Restful
    {
        $controller = new class extends Restful {
            /**
             * Disable normal REST initialization for this trait-focused test.
             */
            public function initialize(): void
            {
            }

            public function loadModel(?string $modelName = null): ModelInterface
            {
                $modelName ??= $this->getModelName();

                if ($modelName === ModelColumnMappedModel::class) {
                    return new ModelColumnMappedModel(null, $this->getDI());
                }

                if ($modelName === ModelColumnAlternateModel::class) {
                    return new ModelColumnAlternateModel(null, $this->getDI());
                }

                if ($modelName === ModelColumnMetadataModel::class) {
                    $metadata = new FakeMetaData();
                    $metadata->attributes = ['id', 'legacy_code'];
                    $metadata->fakeColumnMap = [
                        'legacy_code' => 'legacyCode',
                    ];
                    ModelColumnMetadataModel::$fakeModelsMetaData = $metadata;

                    return new ModelColumnMetadataModel(null, $this->getDI());
                }

                throw new \LogicException('Unexpected model: ' . (string) $modelName);
            }
        };

        $di = $this->di;
        if ($di === null) {
            self::fail('DI container was not initialized.');
        }

        $controller->setDI($di);
        $controller->setModelName($modelName);

        return $controller;
    }
}
