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

use PhalconKit\Mvc\View;
use PhalconKit\Tests\Unit\AbstractUnit;

class ViewTest extends AbstractUnit
{
    public function testMinifyFlagDefaultsToFalseAndCanBeChanged(): void
    {
        $view = new View();

        $this->assertFalse($view->getMinify());

        $view->setMinify(true);

        $this->assertTrue($view->getMinify());
    }

    public function testGetContentReturnsOriginalContentWhenMinifyDisabled(): void
    {
        $view = new View();
        $content = "<div>  Hello  </div>\n<!-- comment -->";

        $view->setContent($content);

        $this->assertSame($content, $view->getContent(false));
    }

    public function testGetContentCanMinifyExplicitly(): void
    {
        $view = new View();
        $view->setContent("<div>  Hello  </div>\n<!-- comment -->\n<span> World </span>");

        $this->assertSame('<div> Hello </div> <span> World </span>', $view->getContent(true));
    }

    public function testGetContentUsesStoredMinifyFlag(): void
    {
        $view = new View();
        $view->setMinify(true);
        $view->setContent("<div>\n  Hello\n</div>");

        $this->assertSame('<div> Hello</div>', $view->getContent());
    }

    public function testGetContentPreservesConditionalCommentsDuringMinify(): void
    {
        $view = new View();
        $view->setContent('<!--[if IE]>keep<![endif]--><!-- remove --><div>ok</div>');

        $this->assertSame('<!--[if IE]>keep<![endif]--><div>ok</div>', $view->getContent(true));
    }
}
