<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/../bocasucia/bocasucia_generator.php';
require __DIR__ . '/truck_config_generator.php';

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;

$config = [
];

// Load the driver(s) you want to use
DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

// Create an instance
$botman = BotManFactory::create($config);

// Give the bot something to listen for.
$botman->hears('turd', function (BotMan $bot) {

    $ontology = file_get_contents("../bocasucia/ontology.json");
    $lexicon = file_get_contents("../bocasucia/lexicon.json");

    $gen = new BocaSuciaGenerator($ontology, $lexicon);
    
    $bot->reply($gen->generate(array( 'level' => 'polite' )));
});

$botman->hears('hello', function (BotMan $bot) {
    $bot->reply('Hello there. Ask me to sell you a pickup truck.');
});

$botman->hears('Sell me a pickup truck.', function (BotMan $bot) {    
    $bot->userStorage()->save([ 'state' => 'action' ]);
    $bot->reply('Do you want to buy, finance or lease?');
});

$botman->hears('((buy)|(finance)|(lease))', function (BotMan $bot, $action) {    
    if($bot->userStorage()->get('state') != 'action') {
        $bot->reply('Say "hello"');
    }else{
        $bot->userStorage()->save([ 'state' => 'drivetrain', 'action' => $action ]);
        $bot->reply('It comes standard with 4x2 drivetrain, do you want a 4x4 one?');
    }
});

$botman->hears('((yes)|(no))', function (BotMan $bot, $ans) {
    $state = $bot->userStorage()->get('state');
    if($state == "drivetrain"){
        $drivetrain = $ans == "yes" ? "4x4" : "4x2";
        $bot->userStorage()->save([ 'state' => 'box', 'drivetrain' => $drivetrain ]);
        $bot->reply('It comes standard with a long box (6\'4"). Do you want a short box (5\'7")?');
    }elseif($state == "box"){ 
        $box = $ans == "yes" ? "short" : "long";
        $bot->userStorage()->save([ 'state' => 'cabin', 'box' => $box ]);
        $bot->reply('Do you want a quad or crew cabin? The standard is the quad cabin.');
    }else{
        $bot->reply('Say "hello"');
    }        
});


$botman->hears('(4x(4|2))', function (BotMan $bot, $drivetrain) {
    if($bot->userStorage()->get('state') != 'drivetrain') {
        $bot->reply('Say "hello"');
    }else{
        $bot->userStorage()->save([ 'state' => 'box', 'drivetrain' => $drivetrain ]);
        $bot->reply('It comes standard with a long box (6\'4"). Do you want a short box (5\'7")?');
    }
});

$botman->hears('((long)|(short))', function (BotMan $bot, $box) {
    if($bot->userStorage()->get('state') != 'box') {
        $bot->reply('Say "hello"');
    }else{
        $bot->userStorage()->save([ 'state' => 'cabin', 'box' => $box ]);
        $bot->reply('Do you want a quad or crew cabin?');
    }
});

$botman->hears('((quad)|(crew))', function (BotMan $bot, $cab) {
    if($bot->userStorage()->get('state') != 'cabin') {
        $bot->reply('Say "hello"');
    }else{
        $gen = TruckConfigGenerator::NewSealed();

        $bot->reply($gen->generate([
            'action' => $bot->userStorage()->get('action'),
            'drivetrain' => $bot->userStorage()->get('drivetrain'),
            'box' => $bot->userStorage()->get('box'),
            'cabin' => $cab ]));
    }
});

 
// Start listening
$botman->listen();
