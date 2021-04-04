<?php

namespace app;
use Discord\Discord;

class Bot
{
	/**
	 * @var Discord - main object to interact with discord API
	 */
	private $discord;

	public function __construct()
	{
		// initialize discord bot, we only need to provider our secret token
		$options = [
			'token' => $_ENV['BOT_TOKEN'],
		];
		$this->discord = new Discord($options);
	}

	/**
	 * Set the main listener for any message sent inthe discord server
	 */
	public function execute()
	{
		$this->discord->on('ready', function ($discord) {
			echo tt('setup.ready'), PHP_EOL;
			$discord->on('message', function ($message, $discord) {
				echo "{$message->author->username}: {$message->content}",PHP_EOL;
			});
		});
		$this->discord->run();
	}
}