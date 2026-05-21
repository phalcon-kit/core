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

use PhalconKit\Mvc\Model;
use PhalconKit\Mvc\Model\Traits\Locale;

class LocaleTraitDouble extends Model
{
    use Locale;

    public mixed $nameEn = null;

    #[\Override]
    public function initialize(): void
    {
    }

    public function labelEn(string $suffix): string
    {
        return 'label-' . $suffix;
    }

    #[\Override]
    public function readAttribute(string $attribute)
    {
        return $this->{$attribute} ?? null;
    }

    #[\Override]
    public function writeAttribute(string $attribute, mixed $value): void
    {
        $this->{$attribute} = $value;
    }
}
