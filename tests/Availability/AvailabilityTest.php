<?php

namespace NLGen\Tests\Availability;

use NLGen\Grammars\Availability\AvailabilityGenerator;
use NLGen\Grammars\Availability\AvailabilityGrammar;
use PHPUnit\Framework\TestCase;

/**
 * @group Availability
 * @covers \NLGen\Grammars\Availability\AvailabilityGenerator
 * @covers \NLGen\Grammars\Availability\AvailabilityGrammar
 */
class AvailabilityTest extends TestCase
{
    private $ranges = [
        0 => [ [8, 0], [17, 0] ],
        1 => [ [8, 0], [17, 0] ],
        2 => [ [8, 0], [17, 0] ],
        3 => [ [8, 0], [17, 0] ],
        4 => [ [8, 0], [17, 0] ],
        5 => [ [8, 0], [17, 0] ]
    ];
                    
    private $fullRanges = [
        0 => [ [6, 0], [24, 0] ],
        1 => [ [6, 0], [24, 0] ],
        2 => [ [6, 0], [24, 0] ],
        3 => [ [6, 0], [24, 0] ],
        4 => [ [6, 0], [24, 0] ],
        5 => [ [6, 0], [24, 0] ],
        6 => [ [6, 0], [24, 0] ]
    ];
    
    // Monday and Wednesday are the same
    // Tuesday and Saturday are similar
    private $regularWeek = [
            [0, [ 8, 00], [ 8, 50]],
            [0, [10, 10], [10, 40]],
            [0, [11, 30], [11, 40]],
            [0, [12, 00], [13, 00]],
            [0, [14, 20], [14, 50]],
            [0, [15, 30], [16, 00]],
            [0, [16, 40], [17, 00]],
            [1, [ 8, 10], [ 8, 30]],
            [1, [11, 00], [11, 40]],
            [1, [12, 00], [12, 30]],
            [1, [13, 00], [13, 50]],
            [1, [15, 00], [15, 40]],
            [1, [15, 50], [16, 50]],
            [2, [ 8, 00], [ 8, 50]],
            [2, [10, 10], [10, 40]],
            [2, [11, 30], [11, 40]],
            [2, [12, 00], [13, 00]],
            [2, [14, 20], [14, 50]],
            [2, [15, 30], [16, 00]],
            [2, [16, 40], [17, 00]],
            [3, [ 8, 10], [ 8, 40]],
            [3, [ 9, 20], [10, 00]],
            [3, [10, 20], [10, 40]],
            [3, [12, 30], [13, 10]],
            [3, [14, 10], [14, 40]],
            [4, [ 8, 40], [ 9, 00]],
            [4, [10, 00], [10, 30]],
            [4, [12, 50], [14, 40]],
            [4, [15, 50], [16, 50]],
            [5, [ 8, 00], [ 8, 40]],
            [5, [11, 00], [11, 50]],
            [5, [13, 00], [13, 50]],
            [5, [12, 10], [12, 40]],
            [5, [15, 10], [15, 50]],
            [5, [15, 50], [16, 50]]];

    private function apiCall(AvailabilityGenerator $gen, string $json) : string
    {
        $data = json_decode($json, True);
        $coarseness = $data[0];
        $ranges = [];
        foreach($data[1] as $dow=>$pair) {
            $ranges[intval($dow)] = $pair;
        }
        $busyList = $data[2];
        return $gen->generateAvailability($busyList, $ranges, $coarseness, null);
    }
    private function normalize(string $str) : string
    {
        $str = preg_replace("/quite/", "mostly", $str);
        return $str;
    }

    
    /**
     * @test
     */
    public function newGenerator() : void
    {
        $gen = new AvailabilityGenerator();
        $this->assertInstanceOf(AvailabilityGrammar::class, $gen);
    }
    
    /**
     * @test
     */
    public function emptyWeek() : void
    {
        $gen = new AvailabilityGenerator();
        $out = $gen->generateAvailability([], $this->ranges, AvailabilityGenerator::SUCCINCT, null);
        $this->assertEquals($out, "All week is free all day.");
    }
    
    /**
     * @test
     */
    public function fullWeek() : void
    {
        $gen = new AvailabilityGenerator();
        $busyList = [];
        foreach($this->ranges as $dow=>$e) {
            $busyList[] = [ $dow, $e[0], $e[1] ];
        }
        $out = $gen->generateAvailability($busyList, $this->ranges, AvailabilityGenerator::SUCCINCT, null);
        $out = $this->normalize($out);
        $this->assertEquals($out, "All week is busy all day.");
    }
    
    /**
     * @test
     */
    public function fullWeirdWeek() : void
    {
        static::markTestSkipped('The current generator lacks opportunistic aggregation.');
        // currently produces: All week is mostly busy all day. Saturday is busy all day.
        
        $gen = new AvailabilityGenerator();
        $weirdRanges = [
            0 => [ [ 8, 0], [17, 0] ],
            1 => [ [ 8, 0], [17, 0] ],
            2 => [ [ 8, 0], [17, 0] ],
            3 => [ [10, 0], [19, 0] ],
            4 => [ [ 8, 0], [17, 0] ],
            5 => [ [ 8, 0], [12, 0] ]
        ];
        $busyList = [];
        foreach($this->ranges as $dow=>$e) {
            $busyList[] = [ $dow, $e[0], $e[1] ];
        }
        $out = $gen->generateAvailability($busyList, $weirdRanges, AvailabilityGenerator::SUCCINCT, null);
        $out = $this->normalize($out);
        $this->assertEquals($out, "All week is busy all day.");
    }
    
    /**
     * @test
     */
    public function regularWeek() : void
    {
        $gen = new AvailabilityGenerator();
        mt_srand(4);
        $out = $gen->generateAvailability($this->regularWeek, $this->ranges, AvailabilityGenerator::BASE, null);
        $out = $this->normalize($out);
        
        $this->assertEquals($out, "Monday and Wednesday are in the morning mostly free, and from 13 PM to late 16 PM somewhat free; the rest is busy. Tuesday and Saturday are in the early morning busy, from 11 AM to late 13 PM somewhat busy, and from 15 PM to late 16 PM mostly busy; the rest is free. Thursday is in the early morning somewhat free, from 10 AM to half past 12 PM mostly free, and from around 13 PM to 17 PM also mostly free; the rest is busy. Friday is in the morning mostly free, in the mid-afternoon free, and in the late afternoon also free; the rest is busy.");
    }

    /**
     * @test
     */
    public function overlapFiltering() : void
    {
        $gen = new AvailabilityGenerator();
        mt_srand(4);
        $busyList = $this->regularWeek;
        foreach($this->ranges as $dow=>$e) {
            $busyList[] = [ $dow, $e[0], $e[1] ];
        }
        $out = $gen->generateAvailability($busyList, $this->ranges, AvailabilityGenerator::BASE, null);
        $out = $this->normalize($out);
        $this->assertEquals($out, "All week is busy all day.");
    }

    /**
     * @test
     */
    public function myWeek() : void
    {
        $gen = new AvailabilityGenerator();
        $busyList = [
            [3, [16, 30], [17, 30] ],
            [6, [ 6, 55], [11, 41] ],
            [6, [14, 32], [22, 05] ]
        ];
        mt_srand(4);
        $out = $gen->generateAvailability($busyList, $this->fullRanges, AvailabilityGenerator::SPECIFIC, null);
        $out = $this->normalize($out);
        
        $this->assertEquals($out, "Monday, Tuesday, Wednesday, Friday, and Saturday are free all day. Thursday is free from 6 AM to half past 16 PM, and from half past 17 PM to 24 PM; the rest is busy. Sunday is busy from late 6 AM to late 11 AM, and from half past 14 PM to 22 PM; the rest is free.");
    }

    /**
     * @test
     */
    public function noFreeLunch() : void
    {
        $gen = new AvailabilityGenerator();
        $out = $this->apiCall($gen, '[1,{"0":[[9,0],[17,0]],"1":[[9,0],[17,0]],"2":[[9,0],[17,0]],"3":[[9,0],[17,0]],"4":[[9,0],[17,0]],"5":[[9,0],[17,0]]},[[2,[12,0],[12,30]],[2,[12,30],[13,0]]]]');
        $out = $this->normalize($out);
        $this->assertEquals($out, "The mornings are free in the morning. The afternoons, Wednesday is free from 13:00 PM to 17:00 PM. Monday, Tuesday, Thursday, Friday, and Saturday are free in the afternoon.");
    }

    /**
     * @test
     */
    public function morningPerson() : void
    {
        static::markTestSkipped('The morning needs to be ommitted.');
        // currently produces: The mornings are somewhat busy in the morning. The afternoons, Monday, Wednesday, Thursday, Friday, and Saturday are quite free.
        
        $gen = new AvailabilityGenerator();
        $out = $this->apiCall($gen, '[1,{"0":[[9,0],[17,0]],"1":[[9,0],[17,0]],"2":[[9,0],[17,0]],"3":[[9,0],[17,0]],"4":[[9,0],[17,0]],"5":[[9,0],[17,0]]},[[0,[11,30],[12,0]],[2,[11,30],[12,0]],[1,[11,30],[12,0]],[1,[12,30],[13,0]],[4,[12,30],[13,0]],[4,[13,30],[14,0]],[3,[13,30],[14,0]],[2,[14,30],[15,0]],[1,[14,30],[15,0]],[1,[15,30],[16,0]],[2,[15,30],[16,0]],[3,[14,30],[15,0]],[3,[10,30],[11,0]],[3,[9,30],[10,0]],[3,[15,30],[16,0]],[2,[12,30],[13,0]],[0,[12,30],[13,0]],[4,[11,30],[12,0]],[5,[11,30],[12,0]],[0,[10,30],[11,0]],[1,[10,30],[11,0]],[2,[10,30],[11,0]],[4,[10,30],[11,0]],[5,[10,30],[11,0]],[0,[9,30],[10,0]],[1,[9,30],[10,0]],[2,[9,30],[10,0]],[4,[9,30],[10,0]],[5,[9,30],[10,0]],[3,[11,30],[12,0]],[3,[12,30],[13,0]],[5,[12,30],[13,0]],[5,[13,30],[14,0]],[2,[13,30],[14,0]],[1,[13,30],[14,0]],[0,[13,30],[14,0]],[1,[13,0],[13,30]],[1,[12,0],[12,30]],[1,[11,0],[11,30]]]]');
        $out = $this->normalize($out);
        // what happened with Tuesday???
        $this->assertEquals($out, "The mornings are somewhat busy. The afternoons, Monday, Wednesday, Thursday, Friday, and Saturday are quite free.");
    }

    /**
     * @test
     */
    public function randomBroke() : void
    {
        $gen = new AvailabilityGenerator();
        $out = $this->apiCall($gen, '[3,{"0":[[9,0],[17,0]],"1":[[9,0],[17,0]],"2":[[9,0],[17,0]],"3":[[9,0],[17,0]],"4":[[9,0],[17,0]],"5":[[9,0],[17,0]]},[[2,[16,0],[16,30]],[2,[16,30],[17,0]],[2,[17,0],[17,30]],[1,[13,0],[13,30]],[4,[13,0],[13,30]],[4,[14,30],[15,0]],[4,[13,30],[14,0]],[5,[12,0],[12,30]],[5,[11,0],[11,30]],[5,[10,30],[11,0]]]]');
        $out = $this->normalize($out);
        $this->assertEquals($out, "Monday and Thursday are free all day. Tuesday is free from 9:00 AM to 13:00 PM, and from 13:30 PM to 17:00 PM; the rest is busy. Wednesday is free from 9:00 AM to 16:00 PM. Friday is free from 9:00 AM to 13:00 PM, from 14:00 PM to 14:30 PM, and from 15:00 PM to 17:00 PM; the rest is busy. Saturday is free from 9:00 AM to 10:30 AM, from 11:30 AM to 12:00 PM, and in the afternoon; the rest is busy.");
    }
    
}
