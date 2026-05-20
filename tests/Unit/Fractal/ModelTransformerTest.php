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

namespace PhalconKit\Tests\Unit\Fractal;

use PhalconKit\Models\User;
use PhalconKit\Fractal\ModelTransformer;
use PhalconKit\Tests\Unit\AbstractUnit;
use Phalcon\Di\InjectionAwareInterface;

class ModelTransformerTest extends AbstractUnit
{
    public function testTransform(): void
    {
        $this->getDb();

        $model = new User();
        $modelTransformer = new ModelTransformer();
        
        // act
        $transformed = $modelTransformer->transform($model);
        
        // asserts
        $this->assertIsArray($transformed);
        $this->assertEquals($transformed, $model->toArray());
        
        // transformer should be injection aware
        $this->assertInstanceOf(InjectionAwareInterface::class, $modelTransformer);
    }
}
