<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhalconKit\Modules\Cli\Tasks\Traits;

use PhalconKit\Cli\Dispatcher;
use PhalconKit\Support\Helper;

/**
 * Resolve CLI scaffold options, generated PHP headers, paths, and namespaces.
 *
 * Requires Core's CLI dispatcher with normalized camelCase option keys. Paths are
 * composed without creating directories or resolving real paths; relative roots
 * stay relative and a leading slash bypasses the configured root. Directory
 * fragments used below the project root should include their trailing slash.
 * Table filters are cached on first use for the lifetime of the task. File writes
 * and overwrite decisions belong to the consuming scaffold task.
 *
 * @property Dispatcher $dispatcher
 */
trait ScaffoldTrait
{
    // Paths & directories
    protected ?string $namespace = 'PhalconKit';
    
    protected string $directory = './';
    protected string $srcDirectory = 'src/';
    protected string $testsDirectory = 'tests/Unit/';
    
    protected string $enumsDirectory = 'Enums/';
    protected string $modelsDirectory = 'Models/';
    protected string $abstractsDirectory = 'Abstracts/';
    protected string $interfacesDirectory = 'Interfaces/';
    protected string $controllersDirectory = 'Controllers/';
    
    protected string $modelsExtend = '\\PhalconKit\\Models\\AbstractModel';
    protected string $interfacesExtend = '\\PhalconKit\\Mvc\\ModelInterface';
    protected string $testsExtend = '\\PhalconKit\\Tests\\Unit\\AbstractUnit';
    protected string $controllersExtend = '\\PhalconKit\\Modules\\Api\\Controllers\\ControllerAbstract';
    
    protected ?array $whitelistedTables = null;
    protected ?array $excludedTables = null;
    
    public string $licenseStamp = <<<PHP
/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

PHP;
    
    public string $strictTypes = <<<PHP
declare(strict_types=1);

PHP;
    
    /**
     * Return the --license override or default header text.
     *
     * @return string|null Header text, or an empty string when --no-license is set.
     *     The nullable signature is retained for task overrides.
     */
    public function getLicenseStamp(): ?string
    {
        return $this->isNoLicense() ? '' : $this->dispatcher->getParameter('license') ?? $this->licenseStamp;
    }
    
    /**
     * Return the strict_types declaration for a generated PHP file.
     *
     * @return string|null Declaration text, or an empty string when --no-strict-types
     *     is set. The nullable signature is retained for task overrides.
     */
    public function getStrictTypes(): ?string
    {
        return $this->isNoStrictTypes() ? '' : $this->strictTypes;
    }

    /**
     * Builds the normalized opening PHP header for scaffolded files.
     *
     * Optional fragments, such as the license stamp and strict-types
     * declaration, are trimmed before joining so disabled fragments do not
     * leave extra blank lines in generated files.
     *
     * @return string Header text ending with exactly one blank line before the
     *     next top-level statement, typically a namespace declaration.
     */
    public function getPhpFileHeader(): string
    {
        $parts = array_filter(
            array_map(
                static fn (?string $part): string => trim((string) $part),
                [
                    '<?php',
                    $this->getLicenseStamp(),
                    $this->getStrictTypes(),
                ]
            ),
            static fn (string $part): bool => $part !== ''
        );

        return implode("\n\n", $parts) . "\n\n";
    }
    
    /**
     * Checks if the given table is whitelisted.
     * @param string $table The table name to check.
     * @return bool True when --table is empty or includes this exact table name.
     */
    public function isWhitelistedTable(string $table): bool
    {
        if (!isset($this->whitelistedTables)) {
            $this->whitelistedTables = array_filter(explode(',', $this->dispatcher->getParameter('table') ?? ''));
        }
        return empty($this->whitelistedTables) || in_array($table, $this->whitelistedTables);
    }
    
    /**
     * Determines if a table is excluded.
     * @param string $table The name of the table to check.
     * @return bool Returns true if the table is excluded, false otherwise.
     */
    public function isExcludedTable(string $table): bool
    {
        if (!isset($this->excludedTables)) {
            $this->excludedTables = array_filter(explode(',', $this->dispatcher->getParameter('exclude') ?? ''));
        }
        return !empty($this->excludedTables) && in_array($table, $this->excludedTables);
    }
    
    /** Skip controller generation when --no-controllers is set. */
    public function isNoControllers(): bool
    {
        return $this->dispatcher->getParameter('noControllers');
    }
    
    /** Skip model interface generation when --no-interfaces is set. */
    public function isNoInterfaces(): bool
    {
        return $this->dispatcher->getParameter('noInterfaces');
    }
    
    /** Skip abstract model generation when --no-abstracts is set. */
    public function isNoAbstracts(): bool
    {
        return $this->dispatcher->getParameter('noAbstracts');
    }
    
    /** Skip concrete model generation when --no-models is set. */
    public function isNoModels(): bool
    {
        return $this->dispatcher->getParameter('noModels');
    }
    
    /** Skip enum generation when --no-enums is set. */
    public function isNoEnums(): bool
    {
        return $this->dispatcher->getParameter('noEnums');
    }
    
    /** Skip test generation when --no-tests is set. */
    public function isNoTests(): bool
    {
        return $this->dispatcher->getParameter('noTests');
    }

    /** Omit the strict_types declaration when --no-strict-types is set. */
    public function isNoStrictTypes(): bool
    {
        return $this->dispatcher->getParameter('noStrictTypes');
    }
    
    /** Omit the generated license header when --no-license is set. */
    public function isNoLicense(): bool
    {
        return $this->dispatcher->getParameter('noLicense');
    }
    
    /** Omit optional generated documentation when --no-comments is set. */
    public function isNoComments(): bool
    {
        return $this->dispatcher->getParameter('noComments');
    }
    
    /** Omit generated accessors when --no-get-set-methods is set. */
    public function isNoGetSetMethods(): bool
    {
        return $this->dispatcher->getParameter('noGetSetMethods');
    }
    
    /** Omit generated validation methods when --no-validations is set. */
    public function isNoValidations(): bool
    {
        return $this->dispatcher->getParameter('noValidations');
    }
    
    /** Omit generated relation definitions when --no-relationships is set. */
    public function isNoRelationships(): bool
    {
        return $this->dispatcher->getParameter('noRelationships');
    }
    
    /** Omit generated column maps when --no-column-map is set. */
    public function isNoColumnMap(): bool
    {
        return $this->dispatcher->getParameter('noColumnMap');
    }
    
    /** Omit generated source-table assignment when --no-set-source is set. */
    public function isNoSetSource(): bool
    {
        return $this->dispatcher->getParameter('noSetSource');
    }
    
    /** Omit optional generated type declarations when --no-typings is set. */
    public function isNoTypings(): bool
    {
        return $this->dispatcher->getParameter('noTypings');
    }
    
    /** Use the more specific scaffold type mappings requested by --granular-typings. */
    public function isGranularTypings(): bool
    {
        return $this->dispatcher->getParameter('granularTypings');
    }
    
    /** Include Phalcon RawValue in generated types when --add-raw-value-type is set. */
    public function isAddRawValueType(): bool
    {
        return $this->dispatcher->getParameter('addRawValueType');
    }
    
    /** Generate protected model properties when --protected-properties is set. */
    public function isProtectedProperties(): bool
    {
        return $this->dispatcher->getParameter('protectedProperties');
    }
    
    /**
     * Determines if a given path is an absolute path.
     * @param string $path The path to be checked. (default: empty string)
     * @return bool Returns true if the path is an absolute path, false otherwise.
     */
    public function isAbsolutePath(string $path = ''): bool
    {
        return str_starts_with($path, '/');
    }
    
    /**
     * Retrieves the absolute file or directory path.
     *
     * @param string $path The relative or absolute path to the file or directory.
     * @param string $fullPath The full path including directory for the file or directory.
     *
     * @return string The absolute file or directory path. If the given path is absolute, it will be returned as is.
     *                Otherwise, the full path including directory will be returned.
     */
    public function absolutePathOr(string $path = '', string $fullPath = ''): string
    {
        return $this->isAbsolutePath($path) ? $path : $fullPath;
    }
    
    /**
     * Retrieves the directory path for a given file or directory path.
     *
     * @param string $path The relative or absolute path to the file or directory.
     *
     * @return string Path under --directory, or the unchanged absolute $path.
     *     A relative --directory produces a relative result.
     */
    public function getDirectory(string $path = ''): string
    {
        $fullPath = ($this->dispatcher->getParameter('directory') ?? $this->directory) . '/' . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `srcDir` dispatcher option under the project directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getSrcDirectory(string $path = ''): string
    {
        $fullPath = $this->getDirectory($this->dispatcher->getParameter('srcDir') ?? $this->srcDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `testsDir` dispatcher option under the project directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getTestsDirectory(string $path = ''): string
    {
        $fullPath = $this->getDirectory($this->dispatcher->getParameter('testsDir') ?? $this->testsDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `controllersDir` dispatcher option under the source directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getControllersDirectory(string $path = ''): string
    {
        $fullPath = $this->getSrcDirectory($this->dispatcher->getParameter('controllersDir') ?? $this->controllersDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `modelsDir` dispatcher option under the source directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getModelsDirectory(string $path = ''): string
    {
        $fullPath = $this->getSrcDirectory($this->dispatcher->getParameter('modelsDir') ?? $this->modelsDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `interfacesDir` dispatcher option under the models directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getModelsInterfacesDirectory(string $path = ''): string
    {
        $fullPath = $this->getModelsDirectory($this->dispatcher->getParameter('interfacesDir') ?? $this->interfacesDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `enumsDir` dispatcher option under the models directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getEnumsDirectory(string $path = ''): string
    {
        $fullPath = $this->getModelsDirectory($this->dispatcher->getParameter('enumsDir') ?? $this->enumsDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `abstractsDir` dispatcher option under the models directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getAbstractsDirectory(string $path = ''): string
    {
        $fullPath = $this->getModelsDirectory($this->dispatcher->getParameter('abstractsDir') ?? $this->abstractsDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `interfaceDir` dispatcher option under the abstract models directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getAbstractsInterfacesDirectory(string $path = ''): string
    {
        $fullPath = $this->getAbstractsDirectory($this->dispatcher->getParameter('interfaceDir') ?? $this->interfacesDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /**
     * Compose a path using the `modelsDir` dispatcher option under the tests directory.
     *
     * @param string $path Suffix to append; a leading slash returns this path unchanged.
     * @return string Composed path, which may remain relative to the working directory.
     */
    public function getModelsTestsDirectory(string $path = ''): string
    {
        $fullPath = $this->getTestsDirectory($this->dispatcher->getParameter('modelsDir') ?? $this->modelsDirectory) . $path;
        return $this->absolutePathOr($path, $fullPath);
    }
    
    /** Return the model parent class name from `modelsExtend`, falling back to the task default. */
    public function getModelsExtend(): string
    {
        return $this->dispatcher->getParameter('modelsExtend') ?? $this->modelsExtend;
    }
    
    /** Return the model parent interface name from `interfacesExtend`, falling back to the task default. */
    public function getInterfacesExtend(): string
    {
        return $this->dispatcher->getParameter('interfacesExtend') ?? $this->interfacesExtend;
    }
    
    /** Return the test parent class name from `testsExtend`, falling back to the task default. */
    public function getTestsExtend(): string
    {
        return $this->dispatcher->getParameter('testsExtend') ?? $this->testsExtend;
    }
    
    /** Return the controller parent class name from `controllersExtend`, falling back to the task default. */
    public function getControllersExtend(): string
    {
        return $this->dispatcher->getParameter('controllersExtend') ?? $this->controllersExtend;
    }
    
    
    /**
     * Converts a file system path to a PHP namespace.
     * @param string $path The file system path to be converted.
     * @return string The converted PHP namespace.
     */
    public function getNamespaceFromPath(string $path): string
    {
        $baseNamespace = ($this->dispatcher->getParameter('namespace') ?? $this->namespace);
        $namespace = $baseNamespace . '\\' .
            Helper::camelize(
                Helper::uncamelize(
                    str_replace(
                        '/',
                        '\\',
                        ltrim($path, isset($baseNamespace) ? $this->getSrcDirectory() : '')
                    )
                )
            );
        return trim(preg_replace('/\\\\+/', '\\', $namespace) ?? '', '\\');
    }
    
    /** Derive the project root namespace from its configured directory and base namespace. */
    public function getNamespace(): string
    {
        return $this->getNamespaceFromPath($this->getDirectory());
    }
    
    /** Derive the controllers namespace from its configured directory and base namespace. */
    public function getControllersNamespace(): string
    {
        return $this->getNamespaceFromPath($this->getControllersDirectory());
    }
    
    /** Derive the model enums namespace from its configured directory and base namespace. */
    public function getEnumsNamespace(): string
    {
        return $this->getNamespaceFromPath($this->getEnumsDirectory());
    }
    
    /** Derive the models namespace from its configured directory and base namespace. */
    public function getModelsNamespace(): string
    {
        return $this->getNamespaceFromPath($this->getModelsDirectory());
    }
    
    /** Derive the abstract models namespace from its configured directory and base namespace. */
    public function getAbstractsNamespace(): string
    {
        return $this->getNamespaceFromPath($this->getAbstractsDirectory());
    }
    
    /** Derive the model interfaces namespace from its configured directory and base namespace. */
    public function getModelsInterfacesNamespace(): string
    {
        return $this->getNamespaceFromPath($this->getModelsInterfacesDirectory());
    }
    
    /** Derive the abstract model interfaces namespace from its configured directory and base namespace. */
    public function getAbstractsInterfacesNamespace(): string
    {
        return $this->getNamespaceFromPath($this->getAbstractsInterfacesDirectory());
    }
    
    /** Derive the model tests namespace from its configured directory and base namespace. */
    public function getModelsTestsNamespace(): string
    {
        return $this->getNamespaceFromPath($this->getModelsTestsDirectory());
    }
}
