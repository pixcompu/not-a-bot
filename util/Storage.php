<?php

namespace util;

use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Factory;

class Storage
{
	/**
	 * @var Storage
	 */
	private static Storage $instance;

	/**
	 * @var Database
	 */
	private Database $database;

	/**
	 * Storage constructor
	 */
	private function __construct()
	{
		// validate the credentials path, we need storage working for some commands like response
		$credentialsPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $_ENV['FIREBASE_CREDENTIALS_PATH'];
		if(!file_exists($credentialsPath)){
			throw new \Exception('No firebase credentials found on: ' . $credentialsPath);
		}
		$factory = (new Factory)->withServiceAccount($credentialsPath)
			->withDatabaseUri($_ENV['FIREBASE_DATABASE_URL']);
		$this->database = $factory->createDatabase();
	}

	/**
	 * @return Storage
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			self::$instance = new Storage();
		}
		return self::$instance;
	}

	/**
	 * Function used to create a new guild in the database, this function is triggered each time the bot is invited
	 * to a guild
	 * @param $guildId
	 * @param $guildName
	 * @throws \Kreait\Firebase\Exception\DatabaseException
	 */
	public function setGuild($guildId, $guildName)
	{
		$guild = $this->database->getReference('/guilds/' . $guildId);
		$guildValue = $guild->getValue();
		// only initialize the guild if not exists, so we don't end up resetting a guild information on accident
		if(!isset($guildValue)){
			$guild->set([
				'id' => $guildId,
				'name' => $guildName,
				'tier' => 'free'
			]);
		}
	}

	/**
	 * Set a value in the responses collection of a guild
	 * @param $guildId
	 * @param $keyword
	 * @param $collection
	 * @param $value
	 * @throws \Kreait\Firebase\Exception\DatabaseException
	 */
	public function setResponse($guildId, $keyword, $value, $collection = 'responses')
	{
		$response = $this->database->getReference('/guilds/' . $guildId . '/' . $collection . '/' . $keyword);
		$response->set([
			'keyword' => $keyword,
			'value' => $value
		]);
	}

	/**
	 * Get a value from the responses collection of a guild
	 * @param $guildId
	 * @param $keyword
	 * @param $collection
	 * @return mixed
	 * @throws \Kreait\Firebase\Exception\DatabaseException
	 */
	public function getResponse($guildId, $keyword, $collection = 'responses')
	{
		return $this->database
			->getReference('/guilds/' . $guildId . '/' . $collection . '/' . $keyword)
			->getSnapshot()
			->getValue();
	}

	/**
	 * Get all the responses stored for the guild
	 * @param $guildId
	 */
	public function getAllResponses($guildId)
	{
		return $this->database
			->getReference('/guilds/' . $guildId . '/responses')
			->getSnapshot()
			->getValue();
	}
}