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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\MetaDataInterface;

final class ModelColumnMetadataModel extends Model
{
    public static ?MetaDataInterface $fakeModelsMetaData = null;

    #[\Override]
    public function getModelsMetaData(): MetaDataInterface
    {
        return self::$fakeModelsMetaData ?? parent::getModelsMetaData();
    }
}
