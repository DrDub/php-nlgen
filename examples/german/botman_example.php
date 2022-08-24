<?php

require __DIR__ . '/vendor/autoload.php';

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Cache\RedisCache;

$config = [
];

// Load the driver(s) you want to use
DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

// Create an instance
$botman = BotManFactory::create($config, new RedisCache('127.0.0.1', 6379));

// Give the bot something to listen for.
$botman->hears('hello', function (BotMan $bot) {
    $bot->startConversation(new ConfigurationConversation("en"));
});

$botman->hears('hallo', function (BotMan $bot) {
    $bot->startConversation(new ConfigurationConversation("de"));
});
 
// Start listening
$botman->listen();
