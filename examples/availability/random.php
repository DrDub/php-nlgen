<?php

/*
 * Copyright (c) 2011-2022 Pablo Ariel Duboue <pablo.duboue@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */

$loader = require __DIR__ . '/vendor/autoload.php';

use NLGen\Grammars\Availability\AvailabilityGenerator;
use NLGen\Grammars\Availability\AvailabilityGrammar;

mt_srand(5);
mt_srand(8);
//mt_srand(9);

$granularities = [ 10, 15, 30, 60 ];
$starts = [ 8, 9 ];
$ends = [ 16, 17, 18 ];
$dowss = [ range(0,4), range(0,5) ];

$gran = $granularities[ mt_rand(0, count($granularities)-1) ];
$start = $starts[ mt_rand(0, count($starts)-1) ];
$end = $ends[ mt_rand(0, count($ends)-1) ];
$dows = $dowss[ mt_rand(0, count($dowss)-1) ];

$table=[];
$busyness = mt_rand() / mt_getrandmax();
foreach($dows as $dow) {
    $time = $start;
    $time_m = 0;
    $day = [];
    while($time < $end) {
        $next = $time;
        $next_m = $time_m + $gran;
        if($next_m >= 60) {
            $next += 1;
            $next_m = 0;
        }
        $day[] = [ [ $time, $time_m ], [ $next, $next_m ], mt_rand() / mt_getrandmax() < $busyness ];
        $time = $next;
        $time_m = $next_m;
    }
    $table[$dow] = $day;
}

$busyList = [];
$ranges = [];
foreach($dows as $dow) {
    $previous = null;
    foreach($table[$dow] as $seg) {
        if($seg[2]) {
            if($previous) { // keep growing
            }else{ // start
                $previous = $seg;
            }
        }else{
            if($previous) {
                $busyList[] = [ $dow, $previous[0], $seg[0] ];
                $previous = null;
            } // else, nothing to do
        }
    }
    if($previous){
        $busyList[] = [ $dow, $previous[0], [$end, 0] ];
    }
    $ranges[$dow] = [ [$start, 0], [$end, 0] ];
}

echo "Granurality: $gran\n";
echo "Busyness: $busyness\n";
echo "Ranges: $start-$end\n";

$dow = 0;
echo "\n$dow: ";
foreach($busyList as $e) {
    if($e[0] != $dow) {
        $dow=$e[0];
        echo "\n$dow: ";
    }
    echo sprintf("%d:%02d",$e[1][0],$e[1][1])."-".sprintf("%d:%02d", $e[2][0],$e[2][1]).", ";
}
echo "\n";
//$s=var_export($ranges, true);
//echo '$ranges = '.preg_replace('/\s+/', ' ', implode(" ", explode("\n", $s)))."\n";
//$s=var_export($busyList, true);
//echo '$busyList = '.preg_replace('/\s+/', ' ', implode(" ", explode("\n", $s)))."\n";

if(false){
    $gen = new AvailabilityGenerator();
}else{
    $class = AvailabilityGrammar::class;
    $path = realpath($loader->findFile($class));
    $lexicon = file_get_contents(dirname($path)."/lexicon_en.json");
    $gen = AvailabilityGrammar::NewSealed('', $lexicon);
}

foreach(range(0,3) as $coarseness) {    
    echo AvailabilityGenerator::COARSENESS[$coarseness].":\n\n";
    $text = $gen->generateAvailability($busyList, $ranges, $coarseness, null);
    echo strtoupper(AvailabilityGenerator::COARSENESS[$coarseness])." OUTPUT: $text\n";
    echo "\n------\n";
}

