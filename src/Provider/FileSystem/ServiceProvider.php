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

namespace PhalconKit\Provider\FileSystem;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the local filesystem service.
 *
 * The provider creates a Flysystem local adapter rooted at the runtime argument,
 * `app.dir.root`, or the current working directory. Use runtime roots for tests
 * or isolated storage, and config roots for application-wide file operations.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'fileSystem';
    
    /**
     * Register the shared `fileSystem` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?string $root = null) use ($di) {

            $config = $di->get('config');
            $root ??= $config->path('app.dir.root') ?? getcwd() ?: '';
            
            return new Filesystem(new LocalFilesystemAdapter($root));
        });
    }
}
