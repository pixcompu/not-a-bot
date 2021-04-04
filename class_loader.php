<?php

/**
 * To use namespaces in PHP we need to define the logic that will include the class
 * when we invoke it
 * @param $className
 */
spl_autoload_register(function($className){
	$path = $className . '.php';
	$path = str_replace('\\', '/', $path);
	require_once __DIR__ . DIRECTORY_SEPARATOR .  $path;
});