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

namespace PhalconKit\Tests\Unit\Support;

use Phalcon\Annotations\AttributesReader;
use Phalcon\Annotations\Reader;
use Phalcon\Db\Column;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaData\Strategy\Annotations;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Support\Fixtures\Phalcon522MetadataModel;
use PHPUnit\Framework\Attributes\DataProvider;

/** Native annotation behavior available through Core's provider and model. */
final class Phalcon522CompatibilityTest extends AbstractUnit
{
    public function testCoreAnnotationsProviderKeepsDocblocksAsTheDefaultReader(): void
    {
        $annotations = $this->di->getShared('annotations');

        $this->assertInstanceOf(Reader::class, $annotations->getReader());
        $source = $annotations->get(Phalcon522MetadataModel::class)->getClassAnnotations()->get('Source');
        $this->assertSame('docblock_records', $source->getArgument(0));
    }

    public function testCoreAnnotationsAdapterAllowsExplicitAttributeReader(): void
    {
        $annotations = $this->di->getShared('annotations');
        $reader = new AttributesReader();
        $annotations->setReader($reader);

        $this->assertSame($reader, $annotations->getReader());
        $source = $annotations->get(Phalcon522MetadataModel::class)->getClassAnnotations()->get('Source');
        $this->assertSame('attribute_records', $source->getArgument(0));
    }

    /** @return array<string, array{bool}> */
    public static function readerModes(): array
    {
        return ['legacy docblocks' => [false], 'PHP attributes' => [true]];
    }

    #[DataProvider('readerModes')]
    public function testMetadataAliasesAndFalsyDefaultsWorkOnCoreModels(bool $attributes): void
    {
        if ($attributes) {
            $this->di->getShared('annotations')->setReader(new AttributesReader());
        }
        $model = new Phalcon522MetadataModel(null, $this->di);
        $metadata = (new Annotations())->getMetaData($model, $this->di);

        $this->assertSame(['id', 'label', 'active', 'quantity'], $metadata[MetaData::MODELS_ATTRIBUTES]);
        $this->assertSame(['id'], $metadata[MetaData::MODELS_PRIMARY_KEY]);
        $this->assertSame('id', $metadata[MetaData::MODELS_IDENTITY_COLUMN]);
        $this->assertSame(['label' => true], $metadata[MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT]);
        $this->assertSame(['label' => true], $metadata[MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE]);
        $this->assertSame(['label' => 'label'], $metadata[MetaData::MODELS_EMPTY_STRING_VALUES]);
        // Docblocks retain numeric literal text; attributes retain PHP values.
        $this->assertSame(['label' => '', 'active' => false, 'quantity' => $attributes ? 0 : '0'], $metadata[MetaData::MODELS_DEFAULT_VALUES]);
        $this->assertSame(Column::TYPE_BOOLEAN, $metadata[MetaData::MODELS_DATA_TYPES]['active']);
        $this->assertSame(Column::BIND_PARAM_BOOL, $metadata[MetaData::MODELS_DATA_TYPES_BIND]['active']);
    }
}
