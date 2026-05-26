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

namespace PhalconKit\Tests\Unit\Mvc\Model\Traits;

use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Mvc\Model\Traits\EagerLoad;
use PhalconKit\Tests\Unit\AbstractUnit;
use ReflectionClass;
use ReflectionNamedType;

class EagerLoadTest extends AbstractUnit
{
    public function testNativeFinderAbstractContractsMatchPatchedPhalconSignatures(): void
    {
        $trait = new ReflectionClass(EagerLoad::class);

        $find = $trait->getMethod('find');
        $findParameters = $find->getParameters();
        $this->assertTrue($find->isAbstract());
        $this->assertTrue($find->isStatic());
        $this->assertCount(1, $findParameters);
        $this->assertSame('mixed', (string)$findParameters[0]->getType());
        $this->assertTrue($findParameters[0]->isDefaultValueAvailable());
        $this->assertNull($findParameters[0]->getDefaultValue());
        $this->assertSame(ResultsetInterface::class, (string)$find->getReturnType());

        $findFirst = $trait->getMethod('findFirst');
        $findFirstParameters = $findFirst->getParameters();
        $findFirstReturnType = $findFirst->getReturnType();
        $this->assertTrue($findFirst->isAbstract());
        $this->assertTrue($findFirst->isStatic());
        $this->assertCount(1, $findFirstParameters);
        $this->assertSame('mixed', (string)$findFirstParameters[0]->getType());
        $this->assertTrue($findFirstParameters[0]->isDefaultValueAvailable());
        $this->assertNull($findFirstParameters[0]->getDefaultValue());
        $this->assertInstanceOf(ReflectionNamedType::class, $findFirstReturnType);
        $this->assertSame('mixed', $findFirstReturnType->getName());
    }
}
