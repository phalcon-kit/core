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

namespace PhalconKit\Tests\Unit\Support;

use Phalcon\Acl\Exceptions\ForbiddenDelimiter;
use Phalcon\Annotations\Adapter\Stream as AnnotationsStream;
use Phalcon\Annotations\Reflection as AnnotationsReflection;
use Phalcon\Db\Column;
use Phalcon\Events\Manager;
use Phalcon\Filter\Validation\Validator\Alpha;
use Phalcon\Filter\Validation\Validator\Numericality;
use Phalcon\Filter\Validation\Validator\StringLength\Min;
use Phalcon\Http\Request\Bag\AbstractBag;
use Phalcon\Mvc\Model\MetaData\Stream as MetadataStream;
use Phalcon\Storage\Adapter\Stream as StorageStream;
use Phalcon\Storage\SerializerFactory;
use PhalconKit\Acl\Acl;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Db\Dialect\Mysql;
use PhalconKit\Filter\Filter;
use PhalconKit\Filter\Validation;
use PhalconKit\Provider\Jwt\Jwt;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Support\Fixtures\Phalcon5201WakeupProbe;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class Phalcon5203CompatibilityTest extends AbstractUnit
{
    public function testAclBuilderRejectsTheInternalDelimiterInEveryNameType(): void
    {
        $permissionSets = [
            'role' => [
                'roles' => [
                    'admin!' => [],
                ],
            ],
            'component' => [
                'roles' => [
                    'admin' => [
                        'components' => [
                            'Admin!Controller' => ['read'],
                        ],
                    ],
                ],
            ],
            'access' => [
                'roles' => [
                    'admin' => [
                        'components' => [
                            'AdminController' => ['read!'],
                        ],
                    ],
                ],
            ],
        ];
        $rejected = [];

        foreach ($permissionSets as $nameType => $permissions) {
            try {
                (new Acl())->get(permissions: $permissions);
            } catch (ForbiddenDelimiter) {
                $rejected[] = $nameType;
            }
        }

        $this->assertSame(array_keys($permissionSets), $rejected);
    }

    public function testStreamBackendsKeepPreviouslyCollidingKeysDistinct(): void
    {
        $directory = $this->temporaryDirectory('phalconkit-5203-key-collision-');
        $annotationsDirectory = $directory . DIRECTORY_SEPARATOR . 'annotations' . DIRECTORY_SEPARATOR;
        $metadataDirectory = $directory . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR;
        mkdir($annotationsDirectory, 0777, true);
        mkdir($metadataDirectory, 0777, true);

        try {
            $annotations = new AnnotationsStream(['annotationsDir' => $annotationsDirectory]);
            $annotations->write(
                'App\\Models\\User_Profile',
                new AnnotationsReflection(['class' => ['underscore']])
            );
            $annotations->write(
                'App\\Models\\User\\Profile',
                new AnnotationsReflection(['class' => ['namespace']])
            );

            $this->assertSame(
                ['class' => ['underscore']],
                $annotations->read('App\\Models\\User_Profile')?->getReflectionData()
            );
            $this->assertSame(
                ['class' => ['namespace']],
                $annotations->read('App\\Models\\User\\Profile')?->getReflectionData()
            );
            $this->assertCount(2, glob($annotationsDirectory . '*.php') ?: []);

            $metadata = new MetadataStream(['metaDataDir' => $metadataDirectory]);
            $metadata->write('App\\Models\\User_Profile', ['underscore']);
            $metadata->write('App\\Models\\User\\Profile', ['namespace']);

            $this->assertSame(['underscore'], $metadata->read('App\\Models\\User_Profile'));
            $this->assertSame(['namespace'], $metadata->read('App\\Models\\User\\Profile'));
            $this->assertCount(2, glob($metadataDirectory . '*.php') ?: []);
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testStorageKeysAndAllowedClassesAreHandledIndependently(): void
    {
        $directory = $this->temporaryDirectory('phalconkit-5203-storage-');

        try {
            $storage = new StorageStream(
                new SerializerFactory(),
                [
                    'storageDir' => $directory . DIRECTORY_SEPARATOR,
                    'allowedClasses' => false,
                ]
            );

            foreach (['cache/key', 'cache\\key', 'cache:key'] as $index => $key) {
                $storage->set($key, 'value-' . $index);
            }

            $this->assertSame('value-0', $storage->get('cache/key'));
            $this->assertSame('value-1', $storage->get('cache\\key'));
            $this->assertSame('value-2', $storage->get('cache:key'));

            Phalcon5201WakeupProbe::$woken = false;
            $storage->set('forbidden-object', new Phalcon5201WakeupProbe());

            $this->assertSame('fallback', $storage->get('forbidden-object', 'fallback'));
            $this->assertFalse($storage->getSerializer()->isSuccess());
            $this->assertFalse(Phalcon5201WakeupProbe::$woken);

            $storage->set('nested-object', ['probe' => new Phalcon5201WakeupProbe()]);

            $stored = $storage->get('nested-object');
            $this->assertIsArray($stored);
            $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $stored['probe']);
            $this->assertFalse(Phalcon5201WakeupProbe::$woken);

            $shardDirectories = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $entry) {
                if ($entry->isDir()) {
                    $shardDirectories[] = $entry->getPathname();
                }
            }

            $this->assertNotEmpty($shardDirectories);
            foreach ($shardDirectories as $shardDirectory) {
                $permissions = fileperms($shardDirectory);
                $this->assertIsInt($permissions);
                $this->assertSame(0, $permissions & 0002, $shardDirectory . ' is world-writable.');
            }
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testFilterAndValidatorsFailClosedForNonStringInput(): void
    {
        $filter = $this->di->get('filter');
        $this->assertInstanceOf(Filter::class, $filter);
        $this->assertSame('', $filter->sanitize('java&#115;cript:alert(1)', Filter::FILTER_URL));
        $this->assertSame('', $filter->sanitize('http://example.com:bad', Filter::FILTER_URL));

        $validators = [
            new Alpha(),
            new Numericality(),
            new Min(['min' => 1]),
        ];

        foreach ($validators as $validator) {
            $validation = new Validation();
            $validation->add('field', $validator);

            $this->assertCount(1, $validation->validate(['field' => ['bypass']]));
        }
    }

    public function testMysqlDialectEscapesColumnComments(): void
    {
        $column = new Column(
            'name',
            [
                'type' => Column::TYPE_VARCHAR,
                'size' => 20,
                'comment' => "owner's note",
            ]
        );

        $sql = (new Mysql())->createTable('records', '', ['columns' => [$column]]);

        $this->assertStringContainsString("COMMENT 'owner''s note'", $sql);
        $this->assertStringNotContainsString("COMMENT 'owner's note'", $sql);
    }

    public function testRequestBagClearRemovesEveryValue(): void
    {
        $bag = new class (['first' => 1, 'second' => 2]) extends AbstractBag {
        };

        $bag->clear();

        $this->assertSame([], $bag->all());
        $this->assertCount(0, $bag);
    }

    public function testEventFalseCanBeMadeFinalWithoutChangingManagerState(): void
    {
        $manager = new Manager();
        $laterListenerRan = false;

        $manager->attach('phalconkit:beforeWrite', static fn(): bool => false);
        $manager->attach(
            'phalconkit:beforeWrite',
            static function () use (&$laterListenerRan): bool {
                $laterListenerRan = true;

                return true;
            }
        );

        $this->assertFalse($manager->fire('phalconkit:beforeWrite', $this, null, true, true));
        $this->assertFalse($laterListenerRan);
        $this->assertFalse($manager->isStopOnFalse());
    }

    public function testJwtAudienceComparisonRejectsBooleanClaim(): void
    {
        $jwt = new Jwt(array_merge((new Config())->pathToArray('security.jwt'), [
            'passphrase' => 'Synthetic!A9-' . bin2hex(random_bytes(64)),
        ]));
        $token = $jwt->buildToken($jwt->builder());
        $segments = explode('.', $token->getToken());
        $payload = json_decode(
            (string)base64_decode(strtr($segments[1], '-_', '+/'), true),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        $this->assertIsArray($payload);
        $payload['aud'] = [true];
        $segments[1] = rtrim(
            strtr(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'),
            '='
        );

        $validator = $jwt->validator($jwt->parseToken(implode('.', $segments)));
        $validator->validateAudience('1');

        $this->assertContains('Validation: audience not allowed', $validator->getErrors());
    }

    private function temporaryDirectory(string $prefix): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(8));
        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            $this->fail(sprintf('Could not create temporary directory "%s".', $directory));
        }

        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }

        rmdir($directory);
    }
}
