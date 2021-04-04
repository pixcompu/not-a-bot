<?php

namespace util;

class Translator
{
	private $translations;
	private static $instance;

	private function __construct()
	{
		// the approach we will take to support multiple languages will be to use translation files
		// those translation files will contain every text the bot will output to the servers
		// we will have one translation file for each language we want to support
		// get the environment languange
		$language = $_ENV['LANG'];

		// try to find the translation file in out lang folder of the project
		$path = __DIR__ . DIRECTORY_SEPARATOR . './../lang/' . $language . '.json';
		$raw = file_get_contents($path);

		// the file could not be read
		if($raw === false){
			throw new \Exception('No language file found on: ' . $path);
		}

		// try to parse the raw test into a PHP array
		$this->translations = json_decode($raw, true);

		// the raw text wasn't a valid JSON
		if(json_last_error() !== JSON_ERROR_NONE){
			throw new \Exception('No valid language JSON found on: ' . $path . ', error: ' . json_last_error_msg());
		}
	}

	/**
	 * we only want to instance translator once, because parsing the JSON translation file is a costly operation
	 * so we implemented a singleton
	 * @return Translator
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			self::$instance = new Translator();
		}

		return self::$instance;
	}

	/**
	 * given a dot notation path, search the string in the JSON translation file
	 * our JSON translation file could look like this
	 * {
	 *      "setup": {
	 *          "ready": "I'm ready!"
	 *      }
	 * }
	 * to get the text "I'm ready!" this function would expect to receive the path "setup.ready"
	 * @param $translationPath - the dot notation path to get a specific value from the JSON
	 * @return mixed - the text in the given path
	 * @throws \Exception
	 */
	public function translate($translationPath)
	{
		$pathParts = explode('.', $translationPath);
		$currentNode = $this->translations;

		// iterate each one of the part of the path going one level deeper in the JSON each time
		foreach ($pathParts as $pathPart){
			if(!isset($currentNode[$pathPart])){
				throw new \Exception('No valid translation found on path: ' . $translationPath);
			}
			$currentNode = $currentNode[$pathPart];
		}

		// the user sent us a path that is incomplete because it point to a object
		if(is_array($currentNode)){
			throw new \Exception('Translation not valid, the path provided must be for a primitive type, object detected for path: ' . $translationPath);
		}

		return $currentNode;
	}
}