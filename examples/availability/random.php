<?php

$loader = require __DIR__ . '/vendor/autoload.php';

use NLGen\Grammars\Availability\AvailabilityGrammar;

mt_srand(5);

$granularities = [ 5, 10, 15, 30, 60 ];
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
//print_r($busyList);

$class = AvailabilityGrammar::class;
$path = realpath($loader->findFile($class));
$lexicon = file_get_contents(dirname($path)."/lexicon_en.json");

$gen = AvailabilityGrammar::NewSealed('', $lexicon);

foreach(range(0,3) as $coarseness) {
    echo AvailabilityGrammar::COARSENESS[$coarseness].":\n\n";

    echo $gen->generateAvailability($busyList, $ranges, $coarseness, null);
    echo "\n------\n";
}

