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

namespace PhalconKit\Tests\Unit\Filter\Validation\Validator;

use Phalcon\Filter\Validation\AbstractValidator;
use Phalcon\Filter\Validation\ValidationInterface;
use Phalcon\Filter\Validation\ValidatorInterface;
use PhalconKit\Filter\Validation;
use PhalconKit\Filter\Validation\Validator\Color;
use PhalconKit\Tests\Unit\AbstractUnit;

class ColorTest extends AbstractUnit
{
    public ValidatorInterface $color;
    public ValidationInterface $validation;
    
    protected function setUp(): void
    {
        $options = [];
        $this->color = new Color($options);
        $this->validation = new Validation();
    }
    
    public function testInstanceOf(): void
    {
        $this->assertInstanceOf(AbstractValidator::class, $this->color);
        $this->assertInstanceOf(ValidatorInterface::class, $this->color);
    }
    
    public function testValidate(): void
    {
        $this->validation->add('field', $this->color);
        
        $validation = $this->validation->validate(['field' => null]);
        $this->assertCount(1, $validation);
        $this->assertEquals('field', $validation->current()->getField());
        
        $this->assertFalse($this->color->validate($this->validation, 'field'));
        $this->assertCount(2, $this->validation->getMessages());
        
        $validation = $this->validation->validate(['field' => '#000000']);
        $this->assertCount(0, $validation);
        
        $this->assertTrue($this->color->validate($this->validation, 'field'));
        $this->assertCount(0, $this->validation->getMessages());
    }

    public function testValidateAcceptsSupportedHexLengths(): void
    {
        $color = new Color();
        $validation = new Validation();
        $validation->add('field', $color);

        foreach (['#fff', '#ffff', '#ffffff', '#ffffffff', '#Aa09Ff'] as $value) {
            $this->assertCount(0, $validation->validate(['field' => $value]));
            $this->assertTrue($color->validate($validation, 'field'));
        }
    }

    public function testValidateRejectsUnsupportedHexFormats(): void
    {
        $color = new Color();
        $validation = new Validation();
        $validation->add('field', $color);

        foreach (['fff', '#ff', '#fffff', '#ggg', '#fffffffff'] as $value) {
            $this->assertCount(1, $validation->validate(['field' => $value]));
            $this->assertFalse($color->validate($validation, 'field'));
        }
    }

    public function testValidateRejectsNonStringValues(): void
    {
        $color = new Color();
        $validation = new Validation();
        $validation->add('field', $color);

        $this->assertCount(1, $validation->validate(['field' => 123]));
        $this->assertFalse($color->validate($validation, 'field'));
    }

    public function testValidateHonorsPerFieldAllowEmptyMap(): void
    {
        $color = new Color(['allowEmpty' => ['field' => true]]);
        $validation = new Validation();
        $validation->add('field', $color);

        $this->assertCount(0, $validation->validate(['field' => null]));
        $this->assertTrue($color->validate($validation, 'field'));

        $color = new Color(['allowEmpty' => ['field' => false]]);
        $validation = new Validation();
        $validation->add('field', $color);

        $this->assertCount(1, $validation->validate(['field' => null]));
        $this->assertFalse($color->validate($validation, 'field'));
    }
}
