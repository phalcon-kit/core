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

namespace PhalconKit\Filter\Validation\Validator;

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\AbstractValidator;
use Phalcon\Filter\Validation\ValidatorInterface;

/**
 * Validate that a field contains a syntactically valid JSON string.
 *
 * The validator intentionally checks strings only. It does not accept decoded
 * arrays, objects, integers, or booleans, because controllers and models that
 * use this validator are asserting the transport/storage representation rather
 * than the decoded PHP value.
 *
 * `json_validate()` is used instead of `json_decode()` so validation does not
 * allocate decoded structures just to prove syntax. The optional `depth` and
 * `flags` options are passed through to PHP's JSON validator.
 */
class Json extends AbstractValidator implements ValidatorInterface
{
    /**
     * Default validation message used when no custom message is configured.
     *
     * @var string
     */
    protected $template = 'Field :field must be a valid json format';
    
    /**
     * Create the JSON validator.
     *
     * Supported options:
     * - `message`/`template`: native Phalcon message customization.
     * - `allowEmpty`: native Phalcon empty-value handling, including
     *   per-field maps, is honored before JSON syntax is checked.
     * - `depth`: maximum nesting depth passed to `json_validate()`.
     * - `flags`: JSON validation flags passed to `json_validate()`.
     *
     * @param array<string, mixed> $options Native Phalcon validator options plus
     *     JSON validation options.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }
    
    /**
     * Validate the configured field value from the Phalcon validation context.
     *
     * @param Validation $validation Current validation context and message
     *     collection.
     * @param mixed $field Field name or field identifier provided by Phalcon.
     *
     * @return bool True when the value is an allowed empty value or valid JSON.
     */
    #[\Override]
    public function validate(Validation $validation, mixed $field): bool
    {
        $value = $validation->getValue($field);
        
        if (is_string($field) && $this->isAllowEmpty($validation, $field)) {
            return true;
        }
        
        $depth = $this->getOption('depth', 512);
        $flags = $this->getOption('flags', 0);
        
        if (!is_string($value) || !json_validate($value, $depth, $flags)) {
            $validation->appendMessage(
                $this->messageFactory($validation, $field)
            );
            
            return false;
        }
        
        return true;
    }
}
