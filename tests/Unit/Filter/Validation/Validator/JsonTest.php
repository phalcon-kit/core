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
use PhalconKit\Filter\Validation\Validator\Json;
use PhalconKit\Tests\Unit\AbstractUnit;

class JsonTest extends AbstractUnit
{
    public ValidatorInterface $json;
    public ValidationInterface $validation;
    
    protected function setUp(): void
    {
        $options = [];
        $this->json = new Json($options);
        $this->validation = new Validation();
    }
    
    public function testInstanceOf(): void
    {
        $this->assertInstanceOf(AbstractValidator::class, $this->json);
        $this->assertInstanceOf(ValidatorInterface::class, $this->json);
    }
    
    public function testValidate(): void
    {
        $this->validation->add('field', $this->json);
        
        $validation = $this->validation->validate(['field' => null]);
        $this->assertCount(1, $validation);
        $this->assertEquals('field', $validation->current()->getField());
        
        $this->assertFalse($this->json->validate($this->validation, 'field'));
        $this->assertCount(2, $this->validation->getMessages());
        
        $validation = $this->validation->validate(['field' => json_encode('test')]);
        $this->assertCount(0, $validation);
        
        $this->assertTrue($this->json->validate($this->validation, 'field'));
        $this->assertCount(0, $this->validation->getMessages());
    }

    public function testValidateAllowsEmptyValuesWhenConfigured(): void
    {
        $json = new Json(['allowEmpty' => true]);
        $validation = new Validation();
        $validation->add('field', $json);

        $this->assertCount(0, $validation->validate(['field' => null]));
        $this->assertTrue($json->validate($validation, 'field'));

        $this->assertCount(0, $validation->validate(['field' => '']));
        $this->assertTrue($json->validate($validation, 'field'));
    }

    public function testValidateHonorsPerFieldAllowEmptyMap(): void
    {
        $json = new Json(['allowEmpty' => ['field' => true]]);
        $validation = new Validation();
        $validation->add('field', $json);

        $this->assertCount(0, $validation->validate(['field' => null]));
        $this->assertTrue($json->validate($validation, 'field'));

        $json = new Json(['allowEmpty' => ['field' => false]]);
        $validation = new Validation();
        $validation->add('field', $json);

        $this->assertCount(1, $validation->validate(['field' => null]));
        $this->assertFalse($json->validate($validation, 'field'));
    }

    public function testValidateRejectsNonStringAndMalformedJsonValues(): void
    {
        $json = new Json();
        $validation = new Validation();
        $validation->add('field', $json);

        $this->assertCount(1, $validation->validate(['field' => ['not' => 'string']]));
        $this->assertFalse($json->validate($validation, 'field'));

        $this->assertCount(1, $validation->validate(['field' => '{"missing":']));
        $this->assertFalse($json->validate($validation, 'field'));
    }

    public function testValidateAcceptsJsonScalarsArraysAndObjects(): void
    {
        $json = new Json();
        $validation = new Validation();
        $validation->add('field', $json);

        foreach (['"string"', '123', 'true', '[]', '{"name":"Ada"}'] as $value) {
            $this->assertCount(0, $validation->validate(['field' => $value]));
            $this->assertTrue($json->validate($validation, 'field'));
        }
    }
}
