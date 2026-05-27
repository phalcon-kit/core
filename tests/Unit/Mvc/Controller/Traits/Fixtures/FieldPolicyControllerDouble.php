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

use PhalconKit\Mvc\Controller\Traits\Query\Fields\ExposeFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\FilterFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\MapFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\OrderFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\SaveFields;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\SearchFields;

final class FieldPolicyControllerDouble
{
    use ExposeFields;
    use FilterFields;
    use MapFields;
    use OrderFields;
    use SaveFields;
    use SearchFields;

    /**
     * Expose the normalized order map for focused policy tests.
     *
     * @return array<string, string>
     */
    public function exposeOrderFieldMap(): array
    {
        return $this->getOrderFieldMap();
    }
}
