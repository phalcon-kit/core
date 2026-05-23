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

namespace PhalconKit\Provider\Imap;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'imap';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?array $options = null) use ($di) {
    
            $config = $di->getConfig();
    
            $options ??= $config->pathToArray('imap', []);

            return new \PhpImap\Mailbox(
                $options['path'] ?? '',
                $options['login'] ?? '',
                $options['password'] ?? '',
                $options['attachmentsDir'] ?? '',
                $options['serverEncoding'] ?? 'UTF-8',
                $options['trimImapPath'] ?? true,
                $options['attachmentFilenameMode'] ?? false,
            );
        });
    }
}
