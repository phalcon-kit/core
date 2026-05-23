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

namespace PhalconKit\Autoload;

use Phalcon\Autoload\Exception as AutoloadException;

/**
 * Phalcon autoloader optimized for framework bootstrap usage.
 *
 * PhalconKit disables the native file-existence callback after construction so
 * autoloading does not perform redundant file checks in production. Namespace
 * registration remains native Phalcon behavior; only the file checking
 * callback is changed.
 */
class Loader extends \Phalcon\Autoload\Loader
{
    /**
     * Create the loader and disable native file checking.
     *
     * @param bool $isDebug Forwarded to the native Phalcon loader constructor.
     *
     * @throws AutoloadException When native Phalcon loader initialization fails.
     */
    public function __construct(bool $isDebug = false)
    {
        parent::__construct($isDebug);
        
        // Do not check file existence.
        $this->setFileCheckingCallback(null);
    }
}
