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

namespace PhalconKit\Tests\Unit\Mvc\View;

use Phalcon\Di\Injectable;
use PhalconKit\Mvc\View\Error;
use PhalconKit\Tests\Unit\AbstractUnit;

class ErrorTest extends AbstractUnit
{
    public function testErrorViewIsInjectable(): void
    {
        $error = new Error();

        $this->assertInstanceOf(Injectable::class, $error);
    }
}
