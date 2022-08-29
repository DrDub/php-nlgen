<?php

require __DIR__ . '/vendor/autoload.php';

$INPUTS=1;

$ontology   = file_get_contents("ontology.json");
$lexicon_en = file_get_contents("lexicon_en.json");
$lexicon_de = file_get_contents("lexicon_de.json");
$gen = MultilingualTruckConfigGenerator::NewSealed($ontology, [ 'en' => $lexicon_en, 'de' => $lexicon_de ], false );

#print_r($gen->mlex['de']);

foreach(['en', 'de'] as $lang) {

    foreach(['buy','finance','lease'] as $action) {
        foreach(['4x4','4x2'] as $drivetrain) {
            foreach(['short','long'] as $box) {
                foreach(['quad','crew'] as $cabin) {
                    foreach(['white','red'] as $color) {
                        if($INPUTS) echo "$lang truck $action $drivetrain $box $cabin $color\n";
                        echo "\t" . $gen->generate([
                            'type'       => 'sentence',
                            'vehicle'    => 'truck',
                            'action'     => $action,
                            'color'      => $color,
                            'drivetrain' => $drivetrain,
                            'box'        => $box,
                            'cabin'      => $cabin ], [ 'lang' => $lang ]) . "\n";
                    }
                }
            }
        }
    }
    foreach(['buy','finance','lease'] as $action) {
        foreach(['inboard','outboard'] as $engine) {
            foreach(['short','long'] as $box) {
                foreach(['quad','crew'] as $cabin) {
                    foreach(['white','red'] as $color) {
                        if($INPUTS) echo "$lang boat $action $drivetrain $box $cabin $color\n";
                        echo "\t" . $gen->generate([
                            'type'       => 'sentence',
                            'vehicle'    => 'boat',
                            'action'     => $action,
                            'color'      => $color,
                            'engine'     => $engine,
                            'box'        => $box,
                            'cabin'      => $cabin ], [ 'lang' => $lang ]) . "\n";
                    }
                }
            }
        }
    }

    foreach(["vehicle","action","engine","drivetrain","box","cabin","color"] as $question) {
        foreach(["truck", "boat"] as $vehicle) {
            if($INPUTS) echo "$lang $vehicle $question\n";
            echo "\t" . $gen->generate([
                'type'       => 'question',
                'need'       => $question,
                'target'     => $vehicle ], [ 'lang' => $lang ]) . "\n";
        }
    }
}
