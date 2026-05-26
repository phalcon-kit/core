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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use Phalcon\Http\Response;
use PhalconKit\Mvc\Controller\Restful;

final class ExportControllerDouble extends Restful
{
    /** @psalm-suppress MissingConstructor Test setup assigns the response. */
    public Response $response;

    /**
     * Disable normal REST controller initialization for export-focused tests.
     */
    #[\Override]
    public function initialize(): void
    {
    }
}
