<?php

namespace NLGen\Tests;

use NLGen\Generator;
use PHPUnit\Framework\TestCase;

/**
 * @group Generator
 * @covers \NLGen\Generator
 */
class GeneratorTest extends TestCase
{
    public static Generator $gen;

    
    public static function setUpBeforeClass(): void
    {
        GeneratorTest::$gen = SimpleGenerator::NewSealed();
    }
    
    /**
     * @test
     */
    public function createSimpleGenerator() : void
    {
        $this->assertInstanceOf(Generator::class, GeneratorTest::$gen);
    }

    /**
     * @test
     */
    public function testA() : void
    {
        $str = GeneratorTest::$gen->generate("A");
        $this->assertEquals($str, "got: 1");
    }
    
    /**
     * @test
     */
    public function testB() : void
    {
        $str = GeneratorTest::$gen->generate("B");
        $this->assertEquals($str, "got: 2, 3");
    }
    
    /**
     * @test
     */
    public function testX() : void
    {
        $str = GeneratorTest::$gen->generate("X");
        $this->assertEquals($str, "got: 2, then got: 2");
    }
}
