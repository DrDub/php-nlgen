<?php

namespace NLGen\Tests;

use NLGen\Ontology;
use PHPUnit\Framework\TestCase;

/**
 * @group Ontology
 * @covers \NLGen\Ontology
 */
class OntologyTest extends TestCase
{
    /**
     * @test
     */
    public function emptyOntology() : void
    {
        $onto = new Ontology('{"":{"":""}}');
        $this->assertInstanceOf(Ontology::class, $onto);
    }
    
    /**
     * @test
     */
    public function has() : void
    {
        $onto = new Ontology('{"test":{"class":"sample"}}');
        $this->assertTrue($onto->has("test"));
    }
    
    /**
     * @test
     */
    public function find_all_by_class() : void
    {
        $onto = new Ontology('{"test1":{"class":"sample"},"test2":{"class":"sample2"},"test3":{"class":"sample"}}');
        $this->assertEquals(count($onto->find_all_by_class("sample")),2);
    }
}
