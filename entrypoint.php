<?php
// vendor libraries
require_once __DIR__.'/vendor/autoload.php';
// logic to load any class we invoke
require_once __DIR__.'/class_loader.php';

// load environment variables, after this line they should be available on $_ENV global variable
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// start listening to discord events
$bot = new \app\Bot();
$bot->execute();