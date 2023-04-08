<?php

namespace util;

class Debug
{
	/**
	 * Dumps the content of a object in the console
	 * @param $content
	 * @return void
	 */
	public static function log($content = null)
	{
		$sanitizedContent = json_encode($content ?? '');
		$trace = debug_backtrace();
		$caller = $trace[1];
		$class = $caller['class'] ?? '';
		$line = $caller['line'] ?? '';
		$message = '>>>>>>>>>>>>> DEBUG ' . $class . ':' . $line . ' LOG: ' . $sanitizedContent;
		echo $message . PHP_EOL;
	}
}