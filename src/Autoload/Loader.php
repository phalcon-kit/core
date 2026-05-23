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
 * Class Loader
 *
 * This class extends the \Phalcon\Autoload\Loader class and disable file checking callback for better performance.
 *
 * @throws AutoloadException When native Phalcon loader initialization fails.
 */
class Loader extends \Phalcon\Autoload\Loader
{
    /**
     * @throws AutoloadException When native Phalcon loader initialization fails.
     */
    public function __construct(bool $isDebug = false)
    {
        parent::__construct($isDebug);
        
        // Do not check file existence.
        $this->setFileCheckingCallback(null);
    }
}
