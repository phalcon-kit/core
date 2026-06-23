<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Cli;

use Phalcon\Di\DiInterface;

/**
 * PhalconKit CLI console wrapper.
 *
 * The class currently preserves native Phalcon console behavior while giving
 * applications and providers a PhalconKit namespace type for CLI bootstraps.
 * CLI modules still rely on native Phalcon console dispatch semantics.
 *
 * @see https://docs.phalcon.io/5.16/application-cli/
 */
class Console extends \Phalcon\Cli\Console
{
    /**
     * Create the CLI console with an optional DI container.
     *
     * @param DiInterface|null $container Native or PhalconKit CLI DI container
     *     forwarded to Phalcon's console constructor.
     */
    public function __construct(?DiInterface $container = null)
    {
        parent::__construct($container);
    }
}
