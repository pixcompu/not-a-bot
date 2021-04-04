<?php

/**
 * To use namespaces in PHP we need to define the logic that will include the class
 * when we invoke it
 *
 * This is purposely in it's own file to import this file in any file were is needed, today entrypoint.php
 * is the only one who uses it, but inn the future entrypoint.php could not be the only way to execute the app
 * @param $className
 */
spl_autoload_register(function($className){
	$path = $className . '.php';
	$path = str_replace('\\', '/', $path);
	require_once __DIR__ . DIRECTORY_SEPARATOR .  $path;
});