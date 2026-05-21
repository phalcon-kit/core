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

namespace PhalconKit\Tests\Unit\Mvc;

use Phalcon\Di\FactoryDefault;
use PhalconKit\Mvc\Application;
use PhalconKit\Tests\Unit\AbstractUnit;

class ApplicationTest extends AbstractUnit
{
    public function testConstructorRegistersApplicationAsSharedService(): void
    {
        $di = new FactoryDefault();
        $application = new Application($di);

        $this->assertInstanceOf(\Phalcon\Mvc\Application::class, $application);
        $this->assertSame($application, $di->get('application'));
        $this->assertSame($di, $application->getDI());
    }
}
