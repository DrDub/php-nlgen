<?php

namespace NLGen\Tests;

use NLGen\Lexicon;
use PHPUnit\Framework\TestCase;

/**
 * @group Lexicon
 * @covers \NLGen\Lexicon
 */
class LexiconTest extends TestCase
{
    /**
     * @test
     */
    public function emptyLexicon() : void
    {
        $lex = new Lexicon(null, '{"":""}');
        $this->assertInstanceOf(Lexicon::class, $lex);
    }
    
    /**
     * @test
     */
    public function bareKeys() : void
    {
        $lex = new Lexicon(null, '{"test":"sample"}');
        $this->assertEquals($lex->string_for_id("test"), "sample");
    }

    /**
     * @test
     */
    public function has() : void
    {
        $lex = new Lexicon(null, '{"test":"sample"}');
        $this->assertTrue($lex->has("test"));
    }
    
    /**
     * @test
     */
    public function query() : void
    {
        $lex = new Lexicon(null, '{"test": [ { "string":"sample1", "pos":"1"}, {"string":"sample2", "pos":"2"}]}');
        $this->assertEquals($lex->query([ "id"=>"test", "pos"=>"2"])[0]['string'], "sample2");
    }
    
    /**
     * @test
     */
    public function sample() : void
    {
        $lex = new Lexicon(null, '{"test": [ { "string":"sample1", "pos":"1"}, {"string":"sample2", "pos":"2"}]}');
        $seen = [];
        foreach(range(0,100) as $x) {
            $seen[$lex->string_for_id("test")] = 1;
        }
        $this->assertEquals(count($seen), 2);
    }

    /**
     * @test
     */
    public function template() : void
    {
        $lex = new Lexicon(null, '{"template": { "mixed": [ "text ", { "slot":"a" }, " text2 ", { "slot":"c" } ] } }');
        $this->assertEquals($lex->string_for_id("template", ["a"=>1,"b"=>2,"c"=>3]), "text 1 text2 3");
    }
    
}
