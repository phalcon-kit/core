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

namespace PhalconKit\Tests\Unit\Html;

use PhalconKit\Tests\Unit\AbstractUnit;

class TagFactoryTest extends AbstractUnit
{
    public \PhalconKit\Tag $tag;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->tag = $this->di->get('tag');
    }
    
    public function testTagFactoryFromDi(): void
    {
        $this->assertInstanceOf(\PhalconKit\Tag::class, $this->tag);
    }
}
