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

namespace PhalconKit\Tests\Unit\Modules\Api\Controllers;

use PHPUnit\Framework\Attributes\DataProvider;
use PhalconKit\Modules\Admin\Controllers\ErrorController as AdminErrorController;
use PhalconKit\Modules\Api\Controllers\ErrorController as ApiErrorController;
use PhalconKit\Modules\Frontend\Controllers\ErrorController as FrontendErrorController;
use PhalconKit\Mvc\Controller\Restful;
use PhalconKit\Tests\Unit\AbstractUnit;

class ErrorControllerTest extends AbstractUnit
{
    /**
     * @param class-string<object> $controllerClass
     */
    #[DataProvider('errorControllerProvider')]
    public function testShippedErrorControllersDoNotExposeModelBackedRestActions(string $controllerClass): void
    {
        $controller = new $controllerClass();

        $this->assertNotInstanceOf(Restful::class, $controller);
        $this->assertFalse(method_exists($controller, 'saveAction'));
        $this->assertTrue(method_exists($controller, 'notFoundAction'));
        $this->assertTrue(method_exists($controller, 'fatalAction'));
    }

    /**
     * @return array<string, array{class-string<object>}>
     */
    public static function errorControllerProvider(): array
    {
        return [
            'api' => [ApiErrorController::class],
            'admin' => [AdminErrorController::class],
            'frontend' => [FrontendErrorController::class],
        ];
    }
}
