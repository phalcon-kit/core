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

use PhalconKit\Di\FactoryDefault;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Controller\Restful;
use PhalconKit\Tests\Unit\AbstractUnit;

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
}
