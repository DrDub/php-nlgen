<?php

$loader = require __DIR__ . '/vendor/autoload.php';

use NLGen\Grammars\Availability\AvailabilityGenerator;
use NLGen\Grammars\Availability\AvailabilityGrammar;
use ICal\ICal;

global $argv,$argc;

if($argc <= 2) {
    echo "Need to specify ical.ics file and monday of week (e.g., 2017-06-01 8:00)\n";
    exit();
}

$monday = new DateTimeImmutable($argv[2]);
          //DateTimeImmutable::createFromFormat("Y-m-d H:i", $argv[2]);
$saturday = $monday->add(new DateInterval("P6D"));

try {
    $ical = new ICal($argv[1], array(
        'defaultSpan'                 => 2,     // Default value
        'defaultTimeZone'             => 'UTC',
        'defaultWeekStart'            => 'MO',  // Default value
        'disableCharacterReplacement' => false, // Default value
        'filterDaysAfter'             => null,  // Default value
        'filterDaysBefore'            => null,  // Default value
        'httpUserAgent'               => null,  // Default value
        'skipRecurrence'              => false, // Default value
    ));
} catch (\Exception $e) {
    die($e);
}

$ranges = [0 => [ [6, 0], [24, 0] ],
           1 => [ [6, 0], [24, 0] ],
           2 => [ [6, 0], [24, 0] ],
           3 => [ [6, 0], [24, 0] ],
           4 => [ [6, 0], [24, 0] ],
           5 => [ [6, 0], [24, 0] ],
           6 => [ [6, 0], [24, 0] ]];

$busyList = [];
$events = $ical->eventsFromRange($monday->format('Y-m-d H:i:s'), $saturday->format('Y-m-d H:i:s'));
foreach($events as $evt){
    $dtstart = $ical->iCalDateToDateTime($evt->dtstart_array[3]);
    $dtend   = $ical->iCalDateToDateTime($evt->dtend_array[3]);
    #echo $dtstart->format('N H:i') . ' -- ' . $dtend->format('H:i')." -- " . $evt->summary. "\n";

    [ $dow, $sh, $sm, $eh, $em ] = explode(" ", $dtstart->format('N H i') . ' ' . $dtend->format('H i'));
    [ $dow, $sh, $sm, $eh, $em ] = [ intval($dow), intval($sh), intval($sm), intval($eh), intval($em) ];
    if($eh == 0) {
        $eh = 24;
        $em = 0;
    }
    if($sh < 6) {
        $sh = 6;
    }
    echo "$dow $sh:$sm -- $eh:$em -- " . $evt->summary. "\n";
    $busyList[] = [ $dow-1, [$sh, $sm], [$eh, $em] ];
}
#print_r($busyList);

if(true){
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


