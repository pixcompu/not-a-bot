<?php

namespace util;

class Debug
{
	public static $accumulateOutput = false;

	private static $outputEntries = [];

	public static function log($content = null)
	{
		$sanitizedContent = json_encode($content ?? '');
		$trace = debug_backtrace();
		$caller = $trace[1];
		$class = $caller['class'] ?? '';
		$line = $caller['line'] ?? '';

		if(self::$accumulateOutput){
			self::$outputEntries[] = [
				'class' => $class,
				'line' => $line,
				'content' => $sanitizedContent,
			];
		} else {
			$message = '>>>>>>>>>>>>> DEBUG ' . $class . ':' . $line . ' LOG: ' . $sanitizedContent;
			echo $message . PHP_EOL;
		}
	}

	public static function getOutput()
	{
		return self::$outputEntries;
	}
}