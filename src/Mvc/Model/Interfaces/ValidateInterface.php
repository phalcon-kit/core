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

namespace PhalconKit\Mvc\Model\Interfaces;

use PhalconKit\Db\Column;
use PhalconKit\Filter\Validation;

/**
 * Build model validation rules without running validation or saving the model.
 *
 * Every add* method mutates and returns the supplied validator for chaining.
 * Field names refer to mapped model attributes; arrays are passed to Phalcon's
 * validator API (for example, a composite uniqueness check). Core's implementation
 * uses model metadata/attributes and the translation service for error messages.
 * Unless a helper states otherwise, allowEmpty=false adds a presence rule.
 *
 * @see \PhalconKit\Mvc\Model\Traits\Validate
 */
interface ValidateInterface
{
    /**
     * Add position, soft-delete, and created/updated/deleted/restored audit rules.
     *
     * @param Validation|null $validator Existing validator, or null to create one.
     * @return Validation The validator after adding rules for declared convention fields.
     */
    public function genericValidation(?Validation $validator = null): Validation;
    
    /**
     * Add a presence rule only when empty values are forbidden.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param bool $allowEmpty True leaves the validator unchanged.
     */
    public function addNotEmptyValidation(Validation $validator, array|string $field, bool $allowEmpty = false): Validation;
    
    /**
     * Add Phalcon's PresenceOf rule with the translated "required" message.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param bool $allowEmpty Forwarded directly to PresenceOf's empty-value policy.
     */
    public function addPresenceValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation;
    
    /**
     * Add numeric and inclusive unsigned INT range checks.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param bool $allowEmpty Skip an optional single field containing null, an empty
     *     string, or Core's case-insensitive SQL NULL sentinel. Zero is a real value.
     */
    public function addUnsignedIntValidation(Validation $validator, array|string $field = 'id', bool $allowEmpty = true): Validation;
    
    /**
     * Add numeric and inclusive unsigned BIGINT range checks.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param bool $allowEmpty Apply the same optional-value policy as unsigned INT.
     * @see self::addUnsignedIntValidation()
     */
    public function addUnsignedBigIntValidation(Validation $validator, array|string $field = 'id', bool $allowEmpty = true): Validation;
    
    /**
     * Add numeric validation and an inclusive range check.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param int $min Lowest permitted value.
     * @param int $max Highest permitted value.
     */
    public function addNumberValidation(Validation $validator, array|string $field, int $min, int $max, bool $allowEmpty = true): Validation;
    
    /**
     * Add inclusive minimum and maximum character-length checks.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param int $minChar Minimum length for a non-empty value.
     * @param int $maxChar Maximum length for a non-empty value.
     */
    public function addStringLengthValidation(Validation $validator, array|string $field, int $minChar = 0, int $maxChar = 255, bool $allowEmpty = true): Validation;
    
    /**
     * Add a domain membership check using Phalcon's default comparison mode.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param array<array-key, mixed> $domainList Allowed values.
     * @see self::addInclusionValidation() For an explicit strict-comparison setting.
     */
    public function addInclusionInValidation(Validation $validator, array|string $field, array $domainList = [], bool $allowEmpty = true): Validation;
    
    /**
     * Accept only true, false, 1, 0, '1', and '0' with strict comparisons.
     *
     * The value is validated without coercion. False and zero are never treated as empty.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param bool $allowEmpty Also accept null and the empty string.
     */
    public function addBooleanValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation;
    
    /**
     * Add a domain membership check with an explicit comparison mode.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param array<array-key, mixed> $domain Allowed values.
     * @param bool $strict Require both value and type to match when true.
     */
    public function addInclusionValidation(Validation $validator, array|string $field, array $domain = [], bool $allowEmpty = true, bool $strict = true): Validation;
    
    /**
     * Add model-backed uniqueness validation, querying the model's connection.
     *
     * @param string|array<string|int, string> $field One unique attribute or a composite set.
     * @see \Phalcon\Filter\Validation\Validator\Uniqueness
     */
    public function addUniquenessValidation(Validation $validator, string|array $field, bool $allowEmpty = true): Validation;
    
    /**
     * Add Phalcon's email-format validator and translated error message.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     */
    public function addEmailValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation;
    
    /**
     * Add a date-format check, skipping optional single-field SQL NULL sentinels.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param string $format PHP date format expected by Phalcon's Date validator.
     */
    public function addDateValidation(Validation $validator, array|string $field, bool $allowEmpty = true, string $format = Column::DATE_FORMAT): Validation;
    
    /**
     * Add a datetime-format check with the same optional policy as date validation.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param string $format PHP date format, including the expected time components.
     */
    public function addDateTimeValidation(Validation $validator, array|string $field, bool $allowEmpty = true, string $format = Column::DATETIME_FORMAT): Validation;
    
    /**
     * Validate a JSON string using Core's JSON validator.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     * @param int $depth Maximum nesting depth passed to JSON decoding.
     * @param int $flags JSON decoding flags; values are not rewritten by this helper.
     */
    public function addJsonValidation(Validation $validator, array|string $field, bool $allowEmpty = true, int $depth = 512, int $flags = 0): Validation;
    
    /**
     * Add Core's hexadecimal color validator.
     *
     * @param array<string|int, string>|string $field Attribute name or names.
     */
    public function addColorValidation(Validation $validator, array|string $field, bool $allowEmpty = true): Validation;
    
    /**
     * Add optional unsigned INT rules only if the named model property exists.
     *
     * @param string $field Identity attribute; an unset auto-generated ID may remain empty.
     */
    public function addIdValidation(Validation $validator, string $field = 'id'): Validation;
    
    /**
     * Validate a declared position property against the unsigned INT range.
     *
     * Core's trait permits RawValue expressions by default for database-side position
     * updates; implementations may expose a separate option to disable that exemption.
     *
     * @param string $field Declared position attribute.
     */
    public function addPositionValidation(Validation $validator, string $field = 'position', bool $allowEmpty = true): Validation;
    
    /**
     * Normalize a declared soft-delete flag to integer YES/NO and add its rules.
     *
     * Unlike the general boolean helper, Core's implementation can write the normalized
     * attribute before validation. It adds strict membership in Column::YES/NO.
     *
     * @param string $field Declared soft-delete attribute.
     */
    public function addSoftDeleteValidation(Validation $validator, string $field = 'deleted', bool $allowEmpty = true): Validation;
    
    /**
     * Add presence and uniqueness rules for a declared UUID attribute.
     *
     * This helper does not check a UUID's textual format or convert binary UUID values.
     *
     * @param string $field Declared UUID attribute.
     */
    public function addUuidValidation(Validation $validator, string $field = 'uuid', bool $allowEmpty = false): Validation;
    
    /**
     * Validate a declared audit user ID and its associated timestamp.
     *
     * An optional empty user ID skips this pair. A non-empty user ID requires the
     * corresponding declared timestamp to match Column::DATETIME_FORMAT.
     *
     * @param string $userIdField Audit actor attribute.
     * @param string $dateField Associated timestamp attribute.
     */
    public function addCrudValidation(Validation $validator, string $userIdField, string $dateField, bool $allowEmpty = true): Validation;
    
    /**
     * Apply audit-pair rules to the creation actor and timestamp.
     *
     * @param string $createdByField Creation actor attribute.
     * @param string $createdAtField Creation timestamp attribute.
     * @see self::addCrudValidation()
     */
    public function addCreatedValidation(Validation $validator, string $createdByField = 'createdBy', string $createdAtField = 'createdAt', bool $allowEmpty = true): Validation;
    
    /**
     * Apply audit-pair rules to the update actor and timestamp.
     *
     * @param string $updatedByField Update actor attribute.
     * @param string $updatedAtField Update timestamp attribute.
     * @see self::addCrudValidation()
     */
    public function addUpdatedValidation(Validation $validator, string $updatedByField = 'updatedBy', string $updatedAtField = 'updatedAt', bool $allowEmpty = true): Validation;
    
    /**
     * Apply audit-pair rules to the deletion actor and timestamp.
     *
     * @param string $deletedField Deletion actor attribute, despite the historical name.
     * @param string $dateField Deletion timestamp attribute.
     * @see self::addCrudValidation()
     */
    public function addDeletedValidation(Validation $validator, string $deletedField = 'deletedBy', string $dateField = 'deletedAt', bool $allowEmpty = true): Validation;
    
    /**
     * Apply audit-pair rules to the restoration actor and timestamp.
     *
     * @param string $restoredByField Restoration actor attribute.
     * @param string $restoredAtField Restoration timestamp attribute.
     * @see self::addCrudValidation()
     */
    public function addRestoredValidation(Validation $validator, string $restoredByField = 'restoredBy', string $restoredAtField = 'restoredAt', bool $allowEmpty = true): Validation;
}
