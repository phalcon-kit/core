<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Integer-backed flags alongside an ordinary numeric attribute. */
class BooleanValidationModelDouble extends ModelBehaviorDouble
{
    public mixed $flag = null;
    public mixed $nullableFlag = null;
    public function addFlagValidation(\PhalconKit\Filter\Validation $validator, array|string $field, bool $allowEmpty): \PhalconKit\Filter\Validation
    {
        foreach ((array)$field as $attribute) {
            $this->normalizeBooleanAttribute($attribute, $allowEmpty);
        }
        return $this->addBooleanValidation($validator, $field, $allowEmpty);
    }
}
