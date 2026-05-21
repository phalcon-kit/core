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

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Mvc\ModelInterface;
use PhalconKit\Models\AuditDetail;

class FakeAuditDetail extends AuditDetail
{
    public array $assigned = [];

    #[\Override]
    public function initialize(): void
    {
    }

    #[\Override]
    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        $this->assigned[] = $data;

        foreach ($data as $field => $value) {
            $this->{$field} = $value;
        }

        return $this;
    }
}
