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

namespace PhalconKit\Tests\Unit\Bootstrap\Fixtures;

use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Mvc\Application;

class BootstrapApplicationDouble extends Application
{
    public ?string $handledUri = null;

    public function handle(string $uri): ResponseInterface|bool
    {
        $this->handledUri = $uri;

        return new Response('mvc-content');
    }
}
