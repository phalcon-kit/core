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

namespace PhalconKit\Html;

use Phalcon\Html\TagFactory as PhalconTagFactory;

/**
 * Framework HTML tag factory.
 *
 * This class intentionally keeps Phalcon's tag-factory behavior while giving
 * applications a PhalconKit-scoped type to register in the DI container. It is
 * the extension point to use when an application wants to replace or decorate
 * the default HTML helpers without changing every consumer that asks for the
 * `tag`/tag-factory service.
 *
 * @see PhalconTagFactory
 */
class TagFactory extends PhalconTagFactory
{
}
