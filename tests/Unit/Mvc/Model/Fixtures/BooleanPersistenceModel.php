<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use PhalconKit\Filter\Validation;
use PhalconKit\Mvc\Model;

/** Real ORM fixture for mapped integer-backed boolean persistence. */
final class BooleanPersistenceModel extends Model
{
    public mixed $id = null;
    public mixed $enabled = null;
    public mixed $optionalFlag = null;
    public mixed $quantity = 42;

    public function initialize(): void
    {
        $this->setSource('boolean_flags');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
    }

    public function columnMap(): array
    {
        return ['id' => 'id', 'is_enabled' => 'enabled', 'optional_flag' => 'optionalFlag', 'quantity' => 'quantity'];
    }

    public function validation(): bool
    {
        $validation = new Validation();
        $this->normalizeBooleanAttribute('enabled', false);
        $this->addBooleanValidation($validation, 'enabled', false);
        $this->normalizeBooleanAttribute('optionalFlag', true);
        $this->addBooleanValidation($validation, 'optionalFlag', true);
        $this->addUnsignedIntValidation($validation, 'quantity', false);
        return $this->validate($validation);
    }
}
