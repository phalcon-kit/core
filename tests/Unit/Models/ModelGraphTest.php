<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhalconKit\Tests\Unit\Models;

use PhalconKit\Mvc\Model;
use PhalconKit\Tests\Unit\AbstractUnit;

class ModelGraphTest extends AbstractUnit
{
    public function testCoreModelsInitializeWithLoadableRelationsWithoutOpeningADatabase(): void
    {
        $this->di->setShared('db', static function (): never {
            throw new \LogicException('Registering model relationships must not open a database.');
        });
        $this->di->setShared('dbr', static function (): never {
            throw new \LogicException('Registering model relationships must not open a database.');
        });

        $manager = $this->di->getShared('modelsManager');
        $files = glob(dirname(__DIR__, 3) . '/src/Models/*.php');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $class = 'PhalconKit\\Models\\' . basename($file, '.php');
            if ((new \ReflectionClass($class))->isAbstract()) {
                continue;
            }

            $model = $manager->load($class);
            $this->assertInstanceOf(Model::class, $model);
            foreach ($manager->getRelations($class) as $relation) {
                $this->assertTrue(class_exists($relation->getReferencedModel()), $class);
                $this->assertTrue(is_a($relation->getReferencedModel(), Model::class, true), $class);
                if ($relation->isThrough()) {
                    $this->assertTrue(class_exists($relation->getIntermediateModel()), $class);
                    $this->assertTrue(is_a($relation->getIntermediateModel(), Model::class, true), $class);
                }
            }
        }
    }
}
