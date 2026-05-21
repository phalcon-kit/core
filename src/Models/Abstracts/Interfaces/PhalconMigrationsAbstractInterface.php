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

namespace PhalconKit\Models\Abstracts\Interfaces;

use Phalcon\Db\RawValue;
use PhalconKit\Mvc\ModelInterface;

/**
 *
 */
interface PhalconMigrationsAbstractInterface extends ModelInterface
{
    /**
     * Returns the value of the field "version"
     * Column: version
     * Attributes: First | Primary | NotNull | Size(255) | Type(2)
     * @return mixed
     */
    public function getVersion(): mixed;
    
    /**
     * Sets the value of the field "version"
     * Column: version
     * Attributes: First | Primary | NotNull | Size(255) | Type(2)
     * @param mixed $version
     * @return void
     */
    public function setVersion(mixed $version): void;
    
    /**
     * Returns the value of the field "startTime"
     * Column: start_time
     * Attributes: NotNull | Type(17)
     * @return mixed
     */
    public function getStartTime(): mixed;
    
    /**
     * Sets the value of the field "startTime"
     * Column: start_time
     * Attributes: NotNull | Type(17)
     * @param mixed $startTime
     * @return void
     */
    public function setStartTime(mixed $startTime): void;
    
    /**
     * Returns the value of the field "endTime"
     * Column: end_time
     * Attributes: NotNull | Type(17)
     * @return mixed
     */
    public function getEndTime(): mixed;
    
    /**
     * Sets the value of the field "endTime"
     * Column: end_time
     * Attributes: NotNull | Type(17)
     * @param mixed $endTime
     * @return void
     */
    public function setEndTime(mixed $endTime): void;
}
