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

/**
 * Registers the IMAP mailbox service.
 *
 * Mailbox options are read from `imap` config and passed to
 * `PhpImap\Mailbox`. The service centralizes mailbox path, login credentials,
 * attachment directory, encoding, and attachment filename behavior for mail
 * ingestion workflows.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'imap';
    
    /**
     * Register the shared `imap` service.
     *
     * Runtime options may be supplied for tests or specialized bootstraps. Empty
     * defaults are preserved so the service can still be constructed in
     * environments where IMAP is configured later by the application.
     */
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
