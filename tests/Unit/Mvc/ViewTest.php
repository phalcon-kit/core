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

    public function testRenderFallsBackToSluggedTemplateName(): void
    {
        $view = $this->createViewWithTemplate('record-status/list-items.phtml', 'Hello <?= $name ?>');
        $view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

        ob_start();
        try {
            $result = $view->render('RecordStatus', 'ListItems', [
                'name' => 'Ada',
            ]);
            $output = ob_get_clean();
        } catch (\Throwable $throwable) {
            ob_end_clean();
            throw $throwable;
        }

        $this->assertNotFalse($result);
        $this->assertSame('Hello Ada', trim((string)$output));
    }

    public function testGetRenderFallsBackToSluggedTemplateName(): void
    {
        $view = $this->createViewWithTemplate('record-status/list-items.phtml', 'Hello <?= $name ?>');

        $content = $view->getRender('RecordStatus', 'ListItems', [
            'name' => 'Lovelace',
        ], static function (View $view): void {
            $view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        });

        $this->assertSame('Hello Lovelace', trim($content));
    }

    private function createViewWithTemplate(string $template, string $content): View
    {
        $viewsDir = sys_get_temp_dir() . '/phalconkit-view-' . bin2hex(random_bytes(8)) . '/';
        $templatePath = $viewsDir . $template;
        $templateDir = dirname($templatePath);

        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0777, true);
        }

        file_put_contents($templatePath, $content);

        $view = new View();
        $view->setDI($this->di);
        $view->setViewsDir($viewsDir);

        return $view;
    }
}
