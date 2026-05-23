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

namespace PhalconKit\Exception;

/**
 * Raised when framework configuration is present but invalid.
 *
 * Use this exception for bad class names, unsupported config values, invalid
 * option shapes, or incompatible configured services. It extends
 * InvalidArgumentException so callers can distinguish configuration mistakes
 * from runtime service failures.
 */
class ConfigurationException extends \InvalidArgumentException implements ExceptionInterface
{
}
