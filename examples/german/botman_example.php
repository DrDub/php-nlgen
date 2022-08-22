<?php

require __DIR__ . '/vendor/autoload.php';

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;

$config = [
];

// Load the driver(s) you want to use
DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

function gen() {
    $lexicon_en = file_get_contents("lexicon_en.json");
    $lexicon_de = file_get_contents("lexicon_de.json");
    return MultilingualTruckConfigGenerator::NewSealed('', [ 'en' => $lexicon_en, 'de' => $lexicon_de ] );
}

// Create an instance
$botman = BotManFactory::create($config);

// Give the bot something to listen for.
$botman->hears('hello', function (BotMan $bot) {
    $bot->userStorage()->save([ 'lang' => 'en' ]);
    $bot->reply(gen()->generate("greet", [ 'lang' => $bot->userStorage()->get('lang') ]));
});

$botman->hears('hallo', function (BotMan $bot) {
    $bot->userStorage()->save([ 'lang' => 'de' ]);
    $bot->reply(gen()->generate("greet", [ 'lang' => $bot->userStorage()->get('lang') ]));
});

$botman->hears('Sell me a pickup truck.', function (BotMan $bot) {
    $lang = $bot->userStorage()->get('lang');
    $bot->userStorage()->save([ 'state' => 'action', 'lang' => $lang ]);
    $bot->reply(gen()->generate("actionq", [ 'lang' => $lang ]));
});

$botman->hears('Verkaufe mir einen Pick-up.', function (BotMan $bot) {    
    $lang = $bot->userStorage()->get('lang');
    $bot->userStorage()->save([ 'state' => 'action', 'lang' => $lang ]);
    $bot->reply(gen()->generate("actionq", [ 'lang' => $lang ]));
});

$botman->hears('((buy)|(finance)|(lease)|(kaufen)|(finanzieren)|(leasen))', function (BotMan $bot, $action) {    
    $lang = $bot->userStorage()->get('lang');
    if($bot->userStorage()->get('state') != 'action') {
        $bot->reply(gen()->generate("reset", [ 'lang' => $lang ]));
    }else{
        $bot->userStorage()->save([ 'state' => 'drivetrain', 'action' => $action, 'lang' => $lang ]);
        $bot->reply(gen()->generate("drivetrainq", [ 'lang' => $lang ]));
    }
});

$botman->hears('((yes)|(no)|(ja)|(nein))', function (BotMan $bot, $ans) {
    $lang  = $bot->userStorage()->get('lang');
    $state = $bot->userStorage()->get('state');
    if($state == "drivetrain"){
        $drivetrain = ($ans == "yes" or $ans == "ja") ? "4x4" : "4x2";
        $bot->userStorage()->save([ 'state' => 'box', 'drivetrain' => $drivetrain, 'lang' => $lang ]);
        $bot->reply(gen()->generate("boxq", [ 'lang' => $lang ]));
    }elseif($state == "box"){ 
        $box = ($ans == "yes" or $ans == "ja") ? "short" : "long";
        $bot->userStorage()->save([ 'state' => 'cabin', 'box' => $box ]);
        $bot->reply(gen()->generate("cabinq", [ 'lang' => $lang ]));
    }else{
        $bot->reply(gen()->generate("reset", [ 'lang' => $lang ]));
    }        
});

$botman->hears('(4x(4|2))', function (BotMan $bot, $drivetrain) {
    $lang = $bot->userStorage()->get('lang');
    if($bot->userStorage()->get('state') != 'drivetrain') {
        $bot->reply(gen()->generate("reset", [ 'lang' => $lang ]));
    }else{
        $bot->userStorage()->save([ 'state' => 'box', 'drivetrain' => $drivetrain, 'lang' => $lang ]);
        $bot->reply(gen()->generate("boxq", [ 'lang' => $lang ]));
    }
});

$botman->hears('((long)|(short)|(langen)|(kurze))', function (BotMan $bot, $box) {
    $lang = $bot->userStorage()->get('lang');
    if($bot->userStorage()->get('state') != 'box') {
        $bot->reply(gen()->generate("reset", [ 'lang' => $lang ]));
    }else{
        $bot->userStorage()->save([ 'state' => 'cabin', 'box' => $box, 'lang' => $lang ]);
        $bot->reply(gen()->generate("cabinq", [ 'lang' => $lang ]));
    }
});

$botman->hears('((quad)|(crew)|([Vv]iererkabine)|([Mm]annschaftskabine))', function (BotMan $bot, $cab) {
    $lang = $bot->userStorage()->get('lang');
    if($bot->userStorage()->get('state') != 'cabin') {
        $bot->reply(gen()->generate("reset", [ 'lang' => $lang ]));
    }else{
        $bot->reply(gen()->generate([
            'action' => $bot->userStorage()->get('action'),
            'drivetrain' => $bot->userStorage()->get('drivetrain'),
            'box' => $bot->userStorage()->get('box'),
            'cabin' => lcfirst($cab) ], [ 'lang' => $lang ]));
    }
});

 
// Start listening
$botman->listen();
