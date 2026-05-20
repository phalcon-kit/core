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

use Dotenv\Dotenv;
use PhalconKit\Support\Env;
use PhalconKit\Tests\Unit\AbstractUnit;

/**
 * Class EnvTest - Tests for the Env class
 *
 * This class contains functions that implement unit tests for the Env class.
 * Each function in this class represents a unit test for a function in the Env class.
 * The name of the function in this class should be the name of the corresponding function in the Env class with 'test' as a prefix.
 */
class EnvTest extends AbstractUnit
{
    public Env $env;
    
    protected function setUp(): void
    {
        /**
         * This setup method is intentionally left empty.
         * This test class does not require any specific initialization or fixtures.
         */
    }
    
    public function testDebugFromDi(): void
    {
        parent::setUp();
        $this->env = $this->di->get('env');
        $this->assertInstanceOf(Env::class, $this->env);
    }
    
    /**
     * Test method `initialize` of the Env class
     *
     * Evaluates if the initialization of Dotenv instance is correctly done with different configurations.
     * It does assertions for the correct set of paths, names, shortCircuit behavior, fileEncoding and type of Dotenv files.
     *
     * @return void
     */
    public function testInitialize(): void
    {
        // Call the `initialize` method.
        $dotenv = Env::load(
            $paths = [dirname(__DIR__, 3)],
            $names = ['.env.testing'],
            $shortCircuit = false,
            $fileEncoding = 'UTF-8',
            $type = 'immutable'
        );
        
        // Check the type of the returned instance.
        $this->assertInstanceOf(Dotenv::class, $dotenv);
        
        // Check that the fields are correctly set.
        $this->assertFalse(Env::getShortCircuit());
        $this->assertEquals($paths, Env::getPaths());
        $this->assertEquals($names, Env::getNames());
        $this->assertEquals($fileEncoding, Env::getFileEncoding());
        $this->assertEquals(ucfirst($type), Env::getType());
        
        // Ensure that a Dotenv instance is initialized after the `initialize` method is called.
        $this->assertInstanceOf(Dotenv::class, Env::$dotenv);
    }
    
    public function testValueCasting(): void
    {
        Env::load(null, '.env.testing-types');
        
        // bool casting
        $this->assertIsBool(Env::get('TEST_BOOL_TRUE'));
        $this->assertTrue(Env::get('TEST_BOOL_TRUE'));
        $this->assertIsBool(Env::get('TEST_BOOL_FALSE'));
        $this->assertFalse(Env::get('TEST_BOOL_FALSE'));
        
        // numeric casting
        $this->assertIsInt(Env::get('TEST_INT'));
        $this->assertIsFloat(Env::get('TEST_FLOAT'));
        $this->assertIsString(Env::get('TEST_STRING'));
        $this->assertIsString(Env::get('TEST_VERSION'));
        
        // default
        $this->assertEquals('default', Env::get('NON_EXISTING_KEY', 'default'));
        $this->assertNull(Env::get('NON_EXISTING_KEY'));
        $this->assertIsString(Env::get('NON_EXISTING_KEY', ''));
        $this->assertNull(Env::get('NON_EXISTING_KEY', null));
        $this->assertTrue(Env::get('NON_EXISTING_KEY', true));
        $this->assertFalse(Env::get('NON_EXISTING_KEY', false));
        $this->assertIsInt(Env::get('NON_EXISTING_KEY', 1));
        $this->assertIsFloat(Env::get('NON_EXISTING_KEY', 1.0));
        $this->assertIsCallable(Env::get('NON_EXISTING_KEY', function () {}));
        $this->assertIsArray(Env::get('NON_EXISTING_KEY', []));
    }
    
    public function testPathsGetSet(): void
    {
        $paths = [__DIR__];
        Env::setPaths($paths);
        $this->assertEquals(Env::getPaths(), $paths);
        
        $paths = __DIR__;
        Env::setPaths($paths);
        $this->assertEquals(Env::getPaths(), $paths);
    }
    
    public function testNamesGetSet(): void
    {
        $names = ['.env'];
        Env::setNames($names);
        $this->assertEquals(Env::getNames(), $names);
        
        $names = '.env';
        Env::setNames($names);
        $this->assertEquals(Env::getNames(), $names);
    }

    public function testLoadUsesConfiguredNames(): void
    {
        Env::setNames(['.env.testing']);
        Env::load();

        $this->assertEquals(['.env.testing'], Env::getNames());
        $this->assertEquals('db', Env::get('DATABASE_HOST'));
    }
    
    public function testShortCircuitGetSet(): void
    {
        Env::setShortCircuit();
        $this->assertTrue(Env::getShortCircuit());
        
        Env::setShortCircuit(null);
        $this->assertTrue(Env::getShortCircuit());
        
        Env::setShortCircuit(true);
        $this->assertTrue(Env::getShortCircuit());
        
        Env::setShortCircuit(false);
        $this->assertFalse(Env::getShortCircuit());
    }
    
    public function testTypeGetSet(): void
    {
        $tests = [
            [null, 'Mutable'],
            ['mutable', 'Mutable'],
            ['immutable', 'Immutable'],
            ['unsafe-mutable', 'UnsafeMutable'],
            ['unsafe-immutable', 'UnsafeImmutable'],
//            ['Mutable', 'Mutable'], // @todo make it work
//            ['Immutable', 'Immutable'], // @todo make it work
//            ['UnsafeMutable', 'UnsafeMutable'], // @todo make it work
//            ['UnsafeImmutable', 'UnsafeImmutable'], // @todo make it work
            ['non-existing', 'Mutable'], // @todo should throw an exception instead
        ];
        
        // test default
        Env::setType();
        $this->assertEquals('Mutable', Env::getType());
        
        // test cases
        foreach ($tests as [$type, $expected]) {
            Env::setType($type);
            $this->assertEquals($expected, Env::getType());
        }
    }
    
    public function testFileEncodingGetSet(): void
    {
        $tests = [
            'ANSI' => 'ANSI',
            'UTF-8' => 'UTF-8',
            'UTF-16' => 'UTF-16',
        ];
        
        foreach ($tests as $encoding => $expected) {
            Env::setFileEncoding($encoding);
            $this->assertEquals($expected, Env::getFileEncoding());
        }
    }
    
    public function testSet(): void
    {
        $tests = [
            'TEST_KEY' => 'value',
            'TEST_BOOL' => false
        ];
        
        foreach ($tests as $key => $value) {
            Env::set($key, $value);
            $this->assertEquals($value, Env::get($key));
            
            Env::load(null, '.env.testing');
            $this->assertNull(Env::get($key));
            $this->assertEquals($value, Env::get($key, $value));
        }
    }
}
