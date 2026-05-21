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

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Fractal\Manager;
use PhalconKit\Fractal\ModelTransformer;
use PhalconKit\Fractal\Transformer;
use PhalconKit\Models\Role;
use PhalconKit\Models\User;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\ProtectedRelationshipUser;
use League\Fractal\Manager as LeagueManager;
use League\Fractal\TransformerAbstract;

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

    public function testManagerExtendsLeagueManager(): void
    {
        $this->assertInstanceOf(LeagueManager::class, new Manager());
    }

    public function testTransformerIsInjectionAwareTransformer(): void
    {
        $transformer = new class extends Transformer {
            public function transform(array $item): array
            {
                return $item;
            }
        };

        $this->assertInstanceOf(TransformerAbstract::class, $transformer);
        $this->assertInstanceOf(InjectionAwareInterface::class, $transformer);

        $transformer->setDI($this->di);
        $this->assertSame($this->di, $transformer->getDI());
        $this->assertSame(['id' => 1], $transformer->transform(['id' => 1]));
    }

    public function testRelationIncludeHelpersUseDirtyAndLoadedRelatedCaches(): void
    {
        $transformer = new class extends Transformer {
            public function transform(mixed $item): array
            {
                return $item instanceof Role ? ['key' => $item->getKey()] : [];
            }

            public function exposeCollection(
                ModelInterface $entity,
                string $alias,
                Transformer $transformer
            ): Collection {
                return $this->includeCollectionIfLoaded($entity, $alias, $transformer);
            }

            public function exposeItem(
                ModelInterface $entity,
                string $alias,
                Transformer $transformer
            ): ?Item {
                return $this->includeItemIfLoaded($entity, $alias, $transformer);
            }
        };

        $user = new ProtectedRelationshipUser();
        $role = new Role();
        $role->setKey('loaded-role');
        $user->setLoadedRelatedAlias('RoleList', [$role]);

        $collection = $transformer->exposeCollection($user, 'RoleList', $transformer);
        $this->assertSame([$role], $collection->getData());

        $loadedRole = new Role();
        $loadedRole->setKey('loaded-role-wins');
        $user->setLoadedRelatedAlias('rolelist', [$loadedRole]);

        $dirtyRole = new Role();
        $dirtyRole->setKey('dirty-role');
        $user->rolelist = [$dirtyRole];

        $collection = $transformer->exposeCollection($user, 'rolelist', $transformer);
        $this->assertSame([$loadedRole], $collection->getData());

        $dirtyUser = new ProtectedRelationshipUser();
        $dirtyUser->rolelist = [$dirtyRole];

        $collection = $transformer->exposeCollection($dirtyUser, 'rolelist', $transformer);
        $this->assertSame([$dirtyRole], $collection->getData());

        $item = $transformer->exposeItem($user, 'CreatedByEntity', $transformer);
        $this->assertNull($item);

        $user->setLoadedRelatedAlias('CreatedByEntity', $role);
        $item = $transformer->exposeItem($user, 'CreatedByEntity', $transformer);

        $this->assertInstanceOf(Item::class, $item);
        $this->assertSame($role, $item->getData());

        $plainModel = $this->createStub(ModelInterface::class);
        $collection = $transformer->exposeCollection($plainModel, 'RoleList', $transformer);
        $this->assertSame([], $collection->getData());
        $this->assertNull($transformer->exposeItem($plainModel, 'CreatedByEntity', $transformer));

        $nonIterableCollection = new ProtectedRelationshipUser();
        $nonIterableCollection->setLoadedRelatedAlias('RoleList', $role);
        $collection = $transformer->exposeCollection($nonIterableCollection, 'RoleList', $transformer);
        $this->assertSame([], $collection->getData());

        $iterableItem = new ProtectedRelationshipUser();
        $iterableItem->setLoadedRelatedAlias('CreatedByEntity', [$role]);
        $this->assertNull($transformer->exposeItem($iterableItem, 'CreatedByEntity', $transformer));

        $nullItem = new ProtectedRelationshipUser();
        $nullItem->setLoadedRelatedAlias('CreatedByEntity', null);
        $this->assertNull($transformer->exposeItem($nullItem, 'CreatedByEntity', $transformer));
    }
}
