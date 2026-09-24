<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model;

use PhalconKit\Filter\Validation;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\BooleanValidationModelDouble;
use PHPUnit\Framework\Attributes\DataProvider;

final class BooleanValidationTest extends AbstractUnit
{
    #[DataProvider('booleanValues')]
    public function testIntegerFlagValidationNormalizesOnlyAcceptedValues(mixed $value, bool $valid, mixed $normalized): void
    {
        $model = new BooleanValidationModelDouble();
        $model->flag = $value;
        $validation = $model->addFlagValidation(new Validation(), 'flag', false);

        self::assertSame($normalized, $model->flag);
        self::assertSame($valid, count($validation->validate(null, $model)) === 0);
    }

    public static function booleanValues(): iterable
    {
        foreach ([true, false, 1, 0, '1', '0'] as $value) {
            yield get_debug_type($value) . ':' . var_export($value, true) => [$value, true, (int)$value];
        }
        foreach ([null, '', 'NULL', 'true', 'false', 'yes', 'no', 'invalid', '01', '1.0', 2, -1, 0.0, 1.0, [], [1], new \stdClass()] as $index => $value) {
            yield 'invalid:' . $index => [$value, false, $value];
        }
    }

    public function testOptionalFlagKeepsNullAndEmptyButValidatesFalseAsZero(): void
    {
        foreach ([null, '', 'NULL', false, 0, '0'] as $value) {
            $model = new BooleanValidationModelDouble();
            $model->nullableFlag = $value;
            $validation = $model->addFlagValidation(new Validation(), 'nullableFlag', true);
            self::assertCount(0, $validation->validate(null, $model));
            self::assertSame(in_array($value, [false, 0, '0'], true) ? 0 : null, $model->nullableFlag);
        }
    }

    public function testNativeBooleanValidationIsStrictAndDoesNotChangeItsType(): void
    {
        foreach ([true, false, 1, 0, '1', '0', 2, 'invalid', [], null, ''] as $value) {
            $model = new BooleanValidationModelDouble();
            $model->flag = $value;
            $validation = $model->addBooleanValidation(new Validation(), 'flag', false);
            self::assertSame(in_array($value, [true, false, 1, 0, '1', '0'], true), count($validation->validate(null, $model)) === 0);
            self::assertSame($value, $model->flag);
        }
    }

    public function testOrdinaryIntegersKeepTheirExistingNumericRules(): void
    {
        $model = new BooleanValidationModelDouble();
        foreach ([0, 2, 100, '42'] as $value) {
            $model->id = $value;
            $validation = $model->addUnsignedIntValidation(new Validation(), 'id', false);
            self::assertCount(0, $validation->validate(null, $model));
            self::assertSame($value, $model->id);
        }
        $model->id = false;
        $validation = $model->addUnsignedIntValidation(new Validation(), 'id', false);
        self::assertNotCount(0, $validation->validate(null, $model));
        self::assertFalse($model->id);
    }

    public function testBooleanFieldListsNormalizeEachAttribute(): void
    {
        $model = new BooleanValidationModelDouble();
        $model->flag = false;
        $model->nullableFlag = '1';
        $model->id = 42;
        $validation = $model->addFlagValidation(new Validation(), ['flag', 'nullableFlag'], false);
        self::assertCount(0, $validation->validate(null, $model));
        self::assertSame(0, $model->flag);
        self::assertSame(1, $model->nullableFlag);
        self::assertSame(42, $model->id);
    }

    public function testGenericValidationDoesNotAddUnrequestedFlagRules(): void
    {
        $model = new BooleanValidationModelDouble();
        $model->flag = 'custom-value';
        $messages = array_filter(
            iterator_to_array($model->genericValidation()->validate(null, $model)),
            static fn ($message): bool => in_array('flag', (array)$message->getField(), true)
        );
        self::assertCount(0, $messages);
        self::assertSame('custom-value', $model->flag);
    }

    public function testExistingThreeParameterBooleanOverridesRemainCompatible(): void
    {
        $model = new class extends BooleanValidationModelDouble {
            public function addBooleanValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation
            {
                return parent::addBooleanValidation($validator, $field, $allowEmpty);
            }
        };
        $model->flag = false;
        $validation = $model->addFlagValidation(new Validation(), 'flag', false);
        self::assertCount(0, $validation->validate(null, $model));
        self::assertSame(0, $model->flag);
    }

    public function testSoftDeleteNormalizesFlagsBeforeLifecycleValidation(): void
    {
        foreach ([true, false, 1, 0, '1', '0', 'invalid', 2] as $value) {
            $model = new BooleanValidationModelDouble();
            $model->deleted = $value;
            $validation = $model->addSoftDeleteValidation(new Validation());
            $valid = in_array($value, [true, false, 1, 0, '1', '0'], true);
            self::assertSame($valid, count($validation->validate(null, $model)) === 0);
            self::assertSame($valid ? (int)$value : $value, $model->deleted);
        }
    }
}
