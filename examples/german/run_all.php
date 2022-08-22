<?php

require __DIR__ . '/vendor/autoload.php';

$lexicon_en = file_get_contents("lexicon_en.json");
$lexicon_de = file_get_contents("lexicon_de.json");
$gen = MultilingualTruckConfigGenerator::NewSealed('', [ 'en' => $lexicon_en, 'de' => $lexicon_de ], true );

foreach(['buy','finance','lease'] as $action) {
    foreach(['4x4','4x2'] as $drivetrain) {
        foreach(['short','long'] as $box) {
            foreach(['quad','crew'] as $cabin) {
                echo "$action $drivetrain $box $cabin\n";
                echo "\t" . $gen->generate([ 'action'     => $action,
                                             'drivetrain' => $drivetrain,
                                             'box'        => $box,
                                             'cabin'      => $cabin ], [ 'lang' => 'en' ]) . "\n";
            }
        }
    }
}

foreach(['kaufen','finanzieren','leasen'] as $action) {
    foreach(['4x4','4x2'] as $drivetrain) {
        foreach(['kurze','langen'] as $box) {
            foreach(['viererkabine','mannschaftskabine'] as $cabin) {
                echo "$action $drivetrain $box $cabin\n";
                echo "\t" . $gen->generate([ 'action'     => $action,
                                             'drivetrain' => $drivetrain,
                                             'box'        => $box,
                                             'cabin'      => $cabin ], [ 'lang' => 'de' ]) . "\n";
            }
        }
    }
}

