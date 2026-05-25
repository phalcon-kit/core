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
 * Validate CSS-style hexadecimal color strings.
 *
 * Accepted values must include the leading `#` and contain 3, 4, 6, or 8
 * hexadecimal digits. That covers shorthand RGB/RGBA and full RGB/RGBA forms
 * such as `#fff`, `#ffff`, `#ffffff`, and `#ffffffff`.
 *
 * Non-string values are rejected instead of being cast. This keeps validation
 * strict for request payloads where a color field should not silently accept
 * numbers, arrays, or already-decoded structured input.
 */
class Color extends AbstractValidator implements ValidatorInterface
{
    /**
     * Default validation message used when no custom message is configured.
     *
     * @var string
     */
    protected $template = 'Field :field must be a valid color in hexadecimal format (e.g., #RRGGBB)';
    
    /**
     * Create the color validator.
     *
     * Common Phalcon validator options such as `message`, `template`, and
     * `allowEmpty` are forwarded to the native base validator. The actual color
     * check remains strict: when a value reaches `validate()`, it must be a
     * string in one of the supported hexadecimal formats.
     *
     * @param array<string, mixed> $options Native Phalcon validator options.
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
     * @return bool True when the value is a supported hexadecimal color.
     */
    #[\Override]
    public function validate(Validation $validation, mixed $field): bool
    {
        $value = $validation->getValue($field);
        
        if (!is_string($value) || !$this->isValidColor($value)) {
            $validation->appendMessage(
                $this->messageFactory($validation, $field)
            );
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if a given color is in a valid hexadecimal format.
     *
     * @param string $color Candidate color including the leading `#`.
     *
     * @return bool True for 3, 4, 6, or 8 hexadecimal digits.
     */
    private function isValidColor(string $color): bool
    {
        $pattern = '/^#([A-Fa-f0-9]{3,4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/';
        
        return preg_match($pattern, $color) === 1;
    }
}
