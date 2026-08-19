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

namespace PhalconKit\Modules\Admin\Controllers;

use PhalconKit\Modules\Admin\Controller;
use PhalconKit\Tag;

abstract class AbstractController extends Controller
{
    public function initialize(): void
    {
        Tag::setAttr('html', ['lang' => $this->dispatcher->getParameter('language', 'string', 'en')]);
        Tag::setTitle($this->config->path('core.name') ?: 'Phalcon Kit Admin');
        $this->assets->collection('head')->addCss('/style.css', true, true, [], null, true);
    }
}
