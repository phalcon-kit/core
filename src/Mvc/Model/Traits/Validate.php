<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Mvc\Model\Traits;

use Phalcon\Db\RawValue;
use PhalconKit\Db\Column;
use PhalconKit\Filter\Validation;
use PhalconKit\Filter\Validation\Validator\Color as ColorValidator;
use PhalconKit\Filter\Validation\Validator\Json as JsonValidator;
use Phalcon\Filter\Validation\Validator\Between;
use Phalcon\Filter\Validation\Validator\Date;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\InclusionIn;
use Phalcon\Filter\Validation\Validator\Numericality;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\StringLength\Max;
use Phalcon\Filter\Validation\Validator\StringLength\Min;
use Phalcon\Filter\Validation\Validator\Uniqueness;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEntity;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractLocale;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractMetaData;

trait Validate
{
    use AbstractLocale;
    use AbstractMetaData;
    use AbstractEntity;

    protected function getAllowEmptyOption(bool $allowEmpty = true): bool|array
    {
        return $allowEmpty ? [null, ''] : false;
    }

    protected function shouldSkipOptionalValidation(array|string $field, bool $allowEmpty): bool
    {
        if (!$allowEmpty || is_array($field)) {
            return false;
        }

        $value = $this->readAttribute($field);
        if ($value instanceof RawValue) {
            $value = (string)$value;
        }

        return $value === null || $value === '';
    }
    
    /**
     * Apply generic validation to a validator object.
     *
     * @param Validation|null $validator The validator object to apply the validation rules to. If null, a new Validation object will be created.
     *
     * @return Validation The validator object with the generic validation rules applied.
     */
    public function genericValidation(?Validation $validator = null): Validation
    {
        $validator ??= new Validation();
        
        $this->addPositionValidation($validator);
        $this->addSoftDeleteValidation($validator);
        $this->addCreatedValidation($validator);
        $this->addUpdatedValidation($validator);
        $this->addDeletedValidation($validator);
        $this->addRestoredValidation($validator);
        
        return $validator;
    }
    
    /**
     * Add validation to ensure that a field is not empty
     *
     * @param Validation $validator The validation object to add the validation to
     * @param array|string $field The name of the field to validate
     * @param bool $allowEmpty Whether to allow empty values. Default is false.
     *
     * @return Validation The updated validation object
     */
    public function addNotEmptyValidation(Validation $validator, array|string $field, bool $allowEmpty = false): Validation
    {
        if (!$allowEmpty) {
            $this->addPresenceValidation($validator, $field, $allowEmpty);
        }
        
        return $validator;
    }
    
    /**
     * Add presence validation to a field in a validator object
     *
     * @param Validation $validator The validator object to add the validation to
     * @param array|string $field The name of the field to validate
     * @param bool $allowEmpty Whether to allow empty values for the field or not (default: true)
     *
     * @return Validation The modified validator object after adding the validation
     */
    public function addPresenceValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation
    {
        $validator->add($field, new PresenceOf([
            'message' => $this->_('required'),
            'allowEmpty' => $allowEmpty,
        ]));
        
        return $validator;
    }
    
    /**
     * Add validations for an unsigned integer field
     *
     * @param Validation $validator The validation object to add rules to
     * @param array|string $field The name of the field to validate (default: 'id')
     * @param bool $allowEmpty Whether to allow the field to be empty (default: true)
     *
     * @return Validation The updated validation object with the added rules
     */
    public function addUnsignedIntValidation(Validation $validator, array|string $field = 'id', bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        if ($this->shouldSkipOptionalValidation($field, $allowEmpty)) {
            return $validator;
        }
        
        // Must be numeric
        $validator->add($field, new Numericality([
            'message' => $this->_('not-numeric'),
            'allowEmpty' => $this->getAllowEmptyOption(),
        ]));
        
        // Must be an unsigned integer
        $validator->add($field, new Between([
            'minimum' => Column::MIN_UNSIGNED_INT,
            'maximum' => Column::MAX_UNSIGNED_INT,
            'message' => $this->_('not-an-unsigned-integer'),
            'allowEmpty' => $this->getAllowEmptyOption(),
        ]));
        
        return $validator;
    }
    
    /**
     * Add basic validations for the specified field to ensure it is an unsigned big integer
     *
     * @param Validation $validator The validation object to add rules to
     * @param array|string $field The name of the field to validate (default is 'id')
     * @param bool $allowEmpty Whether empty values are allowed or not (default is true)
     *
     * @return Validation The updated validation object
     */
    public function addUnsignedBigIntValidation(Validation $validator, array|string $field = 'id', bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        if ($this->shouldSkipOptionalValidation($field, $allowEmpty)) {
            return $validator;
        }
        
        // Must be numeric
        $validator->add($field, new Numericality([
            'message' => $this->_('not-numeric'),
            'allowEmpty' => $this->getAllowEmptyOption(),
        ]));
        
        // Must be an unsigned integer
        $validator->add($field, new Between([
            'minimum' => Column::MIN_UNSIGNED_BIGINT,
            'maximum' => Column::MAX_UNSIGNED_BIGINT,
            'message' => $this->_('not-an-unsigned-big-integer'),
            'allowEmpty' => $this->getAllowEmptyOption(),
        ]));
        
        return $validator;
    }
    
    /**
     * Add number validations for a given field
     *
     * @param Validation $validator The validation object to add the validations to
     * @param array|string $field The name of the field to validate
     * @param int $min The minimum value allowed for the field
     * @param int $max The maximum value allowed for the field
     * @param bool $allowEmpty Specifies whether the field can be empty
     *
     * @return Validation The modified validation object with the number validations added
     */
    public function addNumberValidation(Validation $validator, array|string $field, int $min, int $max, bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        // Must be numeric
        $validator->add($field, new Numericality([
            'message' => $this->_('not-numeric'),
            'allowEmpty' => true,
        ]));
        
        // Must be an unsigned integer
        $validator->add($field, new Between([
            'minimum' => $min,
            'maximum' => $max,
            'message' => $this->_('not-between'),
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add string length validations for a field
     *
     * @param Validation $validator The validation object to add the validations to
     * @param array|string $field The name of the field to be validated
     * @param int $minChar The minimum number of characters allowed (default: 0)
     * @param int $maxChar The maximum number of characters allowed (default: 255)
     * @param bool $allowEmpty Whether empty values are allowed (default: true)
     *
     * @return Validation The validation object with the added validations
     */
    public function addStringLengthValidation(Validation $validator, array|string $field, int $minChar = 0, int $maxChar = 255, bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        $validator->add($field, new Min([
            'min' => $minChar,
            'message' => $this->_('min-length'),
            'allowEmpty' => true,
        ]));
        
        $validator->add($field, new Max([
            'max' => $maxChar + 1,
            'message' => $this->_('max-length'),
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add inclusion validation for a field
     *
     * @param Validation $validator The validation object
     * @param array|string $field The name of the field to be validated
     * @param array $domainList The list of valid values for the field
     * @param bool $allowEmpty Set to true to allow empty values (default: true)
     *
     * @return Validation The updated validation object with the inclusion validation added
     */
    public function addInclusionInValidation(Validation $validator, array|string $field, array $domainList = [], bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        $validator->add($field, new InclusionIn([
            'message' => $this->_('not-valid'),
            'domain' => $domainList,
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add basic validations for a boolean field
     * - Must not be empty
     * - Must be a boolean value (1, 0, true, false)
     *
     * @param Validation $validator The validation object to add the validations to
     * @param array|string $field The name of the field to validate
     * @param bool $allowEmpty Whether to allow empty values or not (default: true)
     *
     * @return Validation The updated validation object
     */
    public function addBooleanValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        $validator->add($field, new InclusionIn([
            'message' => $this->_('not-boolean'),
            'domain' => [1, 0, true, false],
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add inclusion validation for a specified field
     *
     * This method adds an inclusion validation rule to the given validator object for the specified field.
     * The inclusion rule checks if the value of the field is included in the specified domain.
     *
     * @param Validation $validator The validator object to which the rule should be added
     * @param array|string $field The name of the field to be validated
     * @param array $domain The array of allowed values for the field
     * @param bool $allowEmpty Whether to allow empty values for the field (default: true)
     * @param bool $strict Whether to use strict comparison for checking inclusion (default: true)
     *
     * @return Validation The updated validator object
     */
    public function addInclusionValidation(Validation $validator, array|string $field, array $domain = [], bool $allowEmpty = true, bool $strict = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        $validator->add($field, new InclusionIn([
            'message' => $this->_('not-valid'),
            'domain' => $domain,
            'strict' => $strict,
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add uniqueness validation for the specified field(s)
     *
     * @param Validation $validator The validation object to add the validation rules to
     * @param string|array $field The field(s) to apply the uniqueness validation to
     * @param bool $allowEmpty Whether to allow empty values for the field(s)
     *
     * @return Validation The modified validation object
     */
    public function addUniquenessValidation(Validation $validator, string|array $field, bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        $validator->add($field, new Uniqueness([
            'message' => $this->_('not-unique'),
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add email validation to a field
     *
     * @param Validation $validator The validator object
     * @param array|string $field The field name to add the validation to
     * @param bool $allowEmpty Whether to allow empty values for the field (default: true)
     *
     * @return Validation The modified validator object
     */
    public function addEmailValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        $validator->add($field, new Email([
            'message' => $this->_('invalid-email'),
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add basic validations for the date field
     * - Must not be empty
     * - Must be a valid date in the specified format
     *
     * @param Validation $validator The validation object to add the validations to
     * @param array|string $field The name of the date field to validate
     * @param bool $allowEmpty Whether to allow empty values for the date field (default: true)
     * @param string $format The expected format of the date field (default: Column::DATE_FORMAT)
     *
     * @return Validation The updated validation object
     */
    public function addDateValidation(Validation $validator, array|string $field, bool $allowEmpty = true, string $format = Column::DATE_FORMAT): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        if ($this->shouldSkipOptionalValidation($field, $allowEmpty)) {
            return $validator;
        }
        
        $validator->add($field, new Date([
            'format' => $format,
            'message' => $this->_('invalid-date-format'),
            'allowEmpty' => $this->getAllowEmptyOption(),
        ]));
        
        return $validator;
    }
    
    /**
     * Add basic validations for the datetime field
     * - Must not be empty
     * - Must be a valid datetime format
     *
     * @param Validation $validator The validation object
     * @param array|string $field The name of the field to validate
     * @param bool $allowEmpty Specifies if the field is allowed to be empty (default: true)
     * @param string $format The format of the datetime (default: Column::DATETIME_FORMAT)
     *
     * @return Validation The updated validation object
     */
    public function addDateTimeValidation(Validation $validator, array|string $field, bool $allowEmpty = true, string $format = Column::DATETIME_FORMAT): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        if ($this->shouldSkipOptionalValidation($field, $allowEmpty)) {
            return $validator;
        }
        
        $validator->add($field, new Date([
            'format' => $format,
            'message' => $this->_('invalid-datetime-format'),
            'allowEmpty' => $this->getAllowEmptyOption(),
        ]));
        
        return $validator;
    }
    
    /**
     * Add validations for a JSON field
     * - Must not be empty (unless allowEmpty is set to true)
     * - Must be a valid JSON string
     *
     * @param Validation $validator The validator object to add the validations to
     * @param array|string $field The name of the JSON field to validate
     * @param bool $allowEmpty Whether to allow an empty value for the field
     * @param int $depth The maximum depth of the JSON string (default: 512)
     * @param int $flags JSON flags to be used (default: 0)
     *
     * @return Validation The updated validator object
     */
    public function addJsonValidation(Validation $validator, array|string $field, bool $allowEmpty = true, int $depth = 512, int $flags = 0): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        $validator->add($field, new JsonValidator([
            'message' => $this->_('not-valid-json'),
            'depth' => $depth,
            'flags' => $flags,
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add basic validations for the color field
     * - Must not be empty (unless $allowEmpty is set to true)
     * - Must be a valid hex color code
     *
     * @param Validation $validator The validation object
     * @param array|string $field The name of the field to validate
     * @param bool $allowEmpty Whether empty values are allowed (default: true)
     *
     * @return Validation The modified validation object
     */
    public function addColorValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation
    {
        $this->addNotEmptyValidation($validator, $field, $allowEmpty);
        
        $validator->add($field, new ColorValidator([
            'message' => $this->_('not-hex-color'),
            'allowEmpty' => true,
        ]));
        
        return $validator;
    }
    
    /**
     * Add basic validations for the id field
     * - Must be an unsigned integer
     *
     * @param Validation $validator The validation object to add validation rules to
     * @param string $field The name of the field to add validations for (default: 'id')
     *
     * @return Validation The updated validation object
     */
    public function addIdValidation(Validation $validator, string $field = 'id'): Validation
    {
        if (property_exists($this, $field)) {
            $this->addUnsignedIntValidation($validator, $field);
        }
        
        return $validator;
    }
    
    /**
     * Add position validation to a validator object.
     *
     * @param Validation $validator The validator object to add the validation rules to.
     * @param string $field The field name to apply the validation rules to. Default is 'position'.
     * @param bool $allowEmpty Whether empty values are allowed. Default is true.
     * @param bool $allowRawValue Whether raw values are allowed. Default is true.
     *
     * @return Validation The updated validator object with the position validation added.
     */
    public function addPositionValidation(Validation $validator, string $field = 'position', bool $allowEmpty = true, bool $allowRawValue = true): Validation
    {
        if (property_exists($this, $field)) {
            $this->addNotEmptyValidation($validator, $field, $allowEmpty);
            
            if ($allowRawValue) {
                $fieldValue = $this->readAttribute($field);
                if ($fieldValue instanceof RawValue) {
                    return $validator;
                }
            }
            
            // Must be numeric
            $validator->add($field, new Numericality([
                'message' => $this->_('not-numeric'),
                'allowEmpty' => true,
            ]));
            
            // Must be an unsigned integer
            $validator->add($field, new Between([
                'minimum' => Column::MIN_UNSIGNED_INT,
                'maximum' => Column::MAX_UNSIGNED_INT,
                'message' => $this->_('not-an-unsigned-integer'),
                'allowEmpty' => true,
            ]));
        }
        
        return $validator;
    }
    
    /**
     * Add soft delete validation to a validator object.
     *
     * @param Validation $validator The validator object to add the validation rules to.
     * @param string $field The field name to apply the validation rules to. Default is 'deleted'.
     * @param bool $allowEmpty Whether empty values are allowed. Default is true.
     *
     * @return Validation The updated validator object with the soft delete validation added.
     */
    public function addSoftDeleteValidation(Validation $validator, string $field = 'deleted', bool $allowEmpty = true): Validation
    {
        if (property_exists($this, $field)) {
            $this->addNotEmptyValidation($validator, $field, $allowEmpty);
            
            // Must be YES or NO
            $validator->add($field, new InclusionIn([
                'message' => $this->_('not-valid'),
                'domain' => [Column::YES, Column::NO],
                'strict' => true,
            ]));
            
            // Must be numeric
            $validator->add($field, new Numericality([
                'message' => $this->_('not-numeric'),
                'allowEmpty' => true,
            ]));
        }
        
        return $validator;
    }
    
    /**
     * Add UUID validation to a validator object.
     *
     * @param Validation $validator The validator object to add the validation rules to.
     * @param string $field The field name to apply the validation rules to. Default is 'uuid'.
     * @param bool $allowEmpty Whether empty values are allowed. Default is false.
     *
     * @return Validation The updated validator object with the UUID validation added.
     */
    public function addUuidValidation(Validation $validator, string $field = 'uuid', bool $allowEmpty = false): Validation
    {
        if (property_exists($this, $field)) {
            $this->addNotEmptyValidation($validator, $field, $allowEmpty);
            
            // Must be unique
            $validator->add($field, new Uniqueness([
                'message' => $this->_('not-unique'),
                'allowEmpty' => true,
            ]));
        }
        
        return $validator;
    }
    
    /**
     * Add CRUD validation to a validator object.
     *
     * @param Validation $validator The validator object to add the validation rules to.
     * @param string $userIdField The field name for the user ID validation rules.
     * @param string $dateField The field name for the date validation rules.
     * @param bool $allowEmpty Whether empty values are allowed. Default is true.
     *
     * @return Validation The updated validator object with the CRUD validation added.
     */
    public function addCrudValidation(Validation $validator, string $userIdField, string $dateField, bool $allowEmpty = true): Validation
    {
        if (property_exists($this, $userIdField)) {
            $this->addNotEmptyValidation($validator, $userIdField, $allowEmpty);
            if ($this->shouldSkipOptionalValidation($userIdField, $allowEmpty)) {
                return $validator;
            }
            
            // Must be numeric
            $validator->add($userIdField, new Numericality([
                'message' => $this->_('not-numeric'),
                'allowEmpty' => $this->getAllowEmptyOption(),
            ]));
            
            // Must be an unsigned integer
            $validator->add($userIdField, new Between([
                'minimum' => Column::MIN_UNSIGNED_INT,
                'maximum' => Column::MAX_UNSIGNED_INT,
                'message' => $this->_('not-an-unsigned-integer'),
                'allowEmpty' => $this->getAllowEmptyOption(),
            ]));
        }
        
        if (property_exists($this, $dateField) && !empty($this->readAttribute($userIdField))) {
            $this->addPresenceValidation($validator, $dateField, false);
            
            // Must be a valid date format
            $validator->add($dateField, new Date([
                'format' => Column::DATETIME_FORMAT,
                'message' => $this->_('invalid-date-format'),
                'allowEmpty' => true,
            ]));
        }
        
        return $validator;
    }
    
    /**
     * Add created validation to a validator object.
     *
     * @param Validation $validator The validator object to add the validation rules to.
     * @param string $createdByField The field name to apply the validation rules for the "created by" user. Default is 'createdBy'.
     * @param string $createdAtField The field name to apply the validation rules for the "created at" timestamp. Default is 'createdAt'.
     * @param bool $allowEmpty Whether empty values are allowed. Default is true.
     *
     * @return Validation The updated validator object with the created validation added.
     */
    public function addCreatedValidation(Validation $validator, string $createdByField = 'createdBy', string $createdAtField = 'createdAt', bool $allowEmpty = true): Validation
    {
        return $this->addCrudValidation($validator, $createdByField, $createdAtField, $allowEmpty);
    }
    
    /**
     * Add updated validation to a validator object.
     *
     * @param Validation $validator The validator object to add the validation rules to.
     * @param string $updatedByField The field name to apply the updated by validation rule to. Default is 'updatedBy'.
     * @param string $updatedAtField The field name to apply the updated at validation rule to. Default is 'updatedAt'.
     * @param bool $allowEmpty Whether empty values are allowed. Default is true.
     *
     * @return Validation The updated validator object with the updated validation added.
     */
    public function addUpdatedValidation(Validation $validator, string $updatedByField = 'updatedBy', string $updatedAtField = 'updatedAt', bool $allowEmpty = true): Validation
    {
        return $this->addCrudValidation($validator, $updatedByField, $updatedAtField, $allowEmpty);
    }
    
    /**
     * Add deleted validation to a validator object.
     *
     * @param Validation $validator The validator object to add the validation rules to.
     * @param string $deletedField The field name to apply the validation rules to for deleted user. Default is 'deletedBy'.
     * @param string $dateField The field name to apply the validation rules to for deletion date. Default is 'deletedAt'.
     * @param bool $allowEmpty Whether empty values are allowed. Default is true.
     *
     * @return Validation The updated validator object with the deleted validation added.
     */
    public function addDeletedValidation(Validation $validator, string $deletedField = 'deletedBy', string $dateField = 'deletedAt', bool $allowEmpty = true): Validation
    {
        return $this->addCrudValidation($validator, $deletedField, $dateField, $allowEmpty);
    }
    
    /**
     * Add restored validation to a validator object.
     *
     * @param Validation $validator The validator object to add the validation rules to.
     * @param string $restoredByField The field name for the restored by information. Default is 'restoredBy'.
     * @param string $restoredAtField The field name for the restored at information. Default is 'restoredAt'.
     * @param bool $allowEmpty Whether empty values are allowed. Default is true.
     *
     * @return Validation The updated validator object with the restored validation added.
     */
    public function addRestoredValidation(Validation $validator, string $restoredByField = 'restoredBy', string $restoredAtField = 'restoredAt', bool $allowEmpty = true): Validation
    {
        return $this->addCrudValidation($validator, $restoredByField, $restoredAtField, $allowEmpty);
    }
}
