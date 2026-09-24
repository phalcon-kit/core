<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Support\Fixtures;

use Phalcon\Annotations\Models\MetaData\Column;
use Phalcon\Annotations\Models\MetaData\Identity;
use Phalcon\Annotations\Models\MetaData\Primary;
use Phalcon\Annotations\Models\MetaData\Source;
use PhalconKit\Mvc\Model;

/**
 * Column metadata expressed in legacy and attribute reader formats.
 *
 * @Source("docblock_records")
 */
#[Source('attribute_records')]
final class Phalcon522MetadataModel extends Model
{
    /** @Column(type="integer") @Primary @Identity */
    #[Column(type: 'integer')]
    #[Primary]
    #[Identity]
    public ?int $id = null;

    /** @Column(type="string", skip_on_insert=true, skip_on_update=true, allow_empty_string=true, default="") */
    #[Column(type: 'string', skipOnInsert: true, skipOnUpdate: true, allowEmptyString: true, defaultValue: '')]
    public ?string $label = null;

    /** @Column(type="boolean", default=false) */
    #[Column(type: 'boolean', defaultValue: false)]
    public ?bool $active = null;

    /** @Column(type="integer", default=0) */
    #[Column(type: 'integer', defaultValue: 0)]
    public ?int $quantity = null;
}
