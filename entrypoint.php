<?php
// vendor libraries
require_once __DIR__.'/vendor/autoload.php';
// logic to load any class we invoke
require_once __DIR__.'/class_loader.php';

// load environment variables, after this line they should be available on $_ENV global variable
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// declare a friendly global function that we can use to translate any application text
function tt($translationPath){
	return \util\Translator::getInstance()->translate($translationPath);
}

// start listening to discord events
$bot = new \app\Bot();
$bot->execute();