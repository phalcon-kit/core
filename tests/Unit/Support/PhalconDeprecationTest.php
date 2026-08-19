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

namespace PhalconKit\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

class PhalconDeprecationTest extends TestCase
{
    /**
     * Legacy interfaces still required by native Phalcon 5.x override
     * signatures or canonical contracts that have not completed migration.
     *
     * @var array<class-string, list<string>>
     */
    private const array LEGACY_TYPE_ALLOWLIST = [
        'Phalcon\\Db\\Adapter\\AdapterInterface' => [
            'src/Mvc/Model/Traits/Relationship.php',
        ],
        'Phalcon\\Db\\ColumnInterface' => [
            'src/Db/Dialect/Mysql.php',
        ],
        'Phalcon\\Logger\\Adapter\\AdapterInterface' => [
            'src/Logger/Loggers.php',
        ],
        'Phalcon\\Logger\\Formatter\\FormatterInterface' => [
            'src/Logger/Loggers.php',
        ],
        'Phalcon\\Support\\Collection\\CollectionInterface' => [
            'src/Mvc/Model.php',
            'src/Mvc/Model/Interfaces/RelationshipInterface.php',
            'src/Mvc/Model/Traits/Relationship.php',
        ],
    ];

    public function testPublishedSourceUsesCanonicalPhalconContracts(): void
    {
        $actual = [];
        foreach ($this->deprecatedPhalconTypes() as $deprecatedType) {
            foreach ($this->auditedFiles() as $relativePath => $contents) {
                if (str_contains($contents, $deprecatedType)) {
                    $actual[$deprecatedType][] = $relativePath;
                }
            }
        }

        foreach ($actual as &$paths) {
            sort($paths);
        }
        unset($paths);

        $expected = self::LEGACY_TYPE_ALLOWLIST;
        foreach ($expected as &$paths) {
            sort($paths);
        }
        unset($paths);
        ksort($actual);
        ksort($expected);

        $this->assertSame(
            $expected,
            $actual,
            'Replace deprecated Phalcon types with Phalcon\\Contracts types, or document a native-signature hold.'
        );
    }

    public function testDispatcherCallsUseCanonicalParameterMethodNames(): void
    {
        $matches = [];
        $pattern = '/(?:\\$this->dispatcher|\\$dispatcher)->(?:getParam|getParams|hasParam|setParam|setParams)\\s*\\(/';

        foreach ($this->sourceFiles() as $relativePath => $contents) {
            if (preg_match($pattern, $contents, $match, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            $line = substr_count(substr($contents, 0, $match[0][1]), "\n") + 1;
            $matches[] = sprintf('%s:%d:%s', $relativePath, $line, $match[0][0]);
        }

        $this->assertSame(
            [],
            $matches,
            'Use getParameter(), getParameters(), hasParameter(), setParameter(), or setParameters().'
        );
    }

    public function testPublishedSourceAvoidsKnownDeprecatedPhalconMembers(): void
    {
        $patterns = [
            '/->(?:escapeCss|escapeHtml|escapeHtmlAttr|escapeJs|escapeUrl|setHtmlQuoteType)\\s*\\(/',
            '/->(?:getKeys|getValues|supportsDefaultValue)\\s*\\(/',
            '/->(?:existsBelongsTo|existsHasMany|existsHasManyToMany|existsHasOne|existsHasOneThrough)\\s*\\(/',
            '/::resetInput\\s*\\(/',
            '/::CRYPT_(?:BLOWFISH|BLOWFISH_Y|EXT_DES|MD5|STD_DES)\\b/',
        ];
        $matches = [];

        foreach ($this->auditedFiles() as $relativePath => $contents) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $contents, $match, PREG_OFFSET_CAPTURE) !== 1) {
                    continue;
                }

                $line = substr_count(substr($contents, 0, $match[0][1]), "\n") + 1;
                $matches[] = sprintf('%s:%d:%s', $relativePath, $line, $match[0][0]);
            }
        }

        $this->assertSame([], $matches, 'Replace deprecated Phalcon member aliases with their canonical APIs.');
    }

    /**
     * @return list<class-string>
     */
    private function deprecatedPhalconTypes(): array
    {
        $stubRoot = $this->projectRoot() . '/vendor/phalcon/ide-stubs/src';
        $types = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($stubRoot));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (!is_string($contents)) {
                continue;
            }

            if (
                preg_match('/namespace\\s+([^;]+);/', $contents, $namespace) !== 1
                || preg_match(
                    '/^(?:final\\s+|abstract\\s+)?(?:interface|class|trait)\\s+(\\w+)/m',
                    $contents,
                    $declaration,
                    PREG_OFFSET_CAPTURE
                ) !== 1
            ) {
                continue;
            }

            $header = substr($contents, 0, $declaration[0][1]);
            if (!str_contains($header, '@deprecated')) {
                continue;
            }

            /** @var class-string $type */
            $type = trim($namespace[1]) . '\\' . $declaration[1][0];
            $types[] = $type;
        }

        sort($types);
        return array_values(array_unique($types));
    }

    /**
     * @return iterable<string, string>
     */
    private function auditedFiles(): iterable
    {
        foreach (['src', 'guides', 'resources/skills'] as $directory) {
            yield from $this->filesUnder($directory, ['md', 'php', 'yaml', 'yml']);
        }
    }

    /**
     * @return iterable<string, string>
     */
    private function sourceFiles(): iterable
    {
        yield from $this->filesUnder('src', ['php']);
    }

    /**
     * @param list<string> $extensions
     * @return iterable<string, string>
     */
    private function filesUnder(string $directory, array $extensions): iterable
    {
        $root = $this->projectRoot();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root . '/' . $directory)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), $extensions, true)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (is_string($contents)) {
                yield substr($file->getPathname(), strlen($root) + 1) => $contents;
            }
        }
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
