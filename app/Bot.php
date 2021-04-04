<?php

namespace app;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;

class Bot
{
	/**
	 * @var Discord - main object to interact with discord API
	 */
	private $discord;

	/**
	 * Bot constructor.
	 * @throws \Discord\Exceptions\IntentException
	 */
	public function __construct()
	{
		// initialize discord bot, we only need to provider our secret token
		$options = [
			'token' => $_ENV['BOT_TOKEN'],
		];
		$this->discord = new Discord($options);
	}

	/**
	 * initialize the discord listener
	 */
	public function execute()
	{
		$this->discord->on('ready', \Closure::fromCallable([$this, 'ready']));
		$this->discord->run();
	}

	/**
	 * configure any action that need to be executed when the bot just loaded
	 */
	private function ready()
	{
		echo tt('setup.ready'), PHP_EOL;
		$this->listen();
	}

	/**
	 * configure every event we want to listen from discord
	 */
	private function listen()
	{
		$this->discord->on(Event::MESSAGE_CREATE, function (Message $message) {
			// example of both replying to a message, showing a image
			$shouldAnswer = strpos($message->content, 'mona china') !== false;
			if($shouldAnswer){
				$message->reply('https://www.stylevore.com/wp-content/uploads/2020/01/0aecae65e9c73f438c2c77120067ce29-1024x1280.jpg');
			}
		});
	}
}