<?php

namespace app;
use Discord\Discord;

class Bot
{
	public function execute()
	{
		// initialize discord bot, we only need to provider our secret token
		$discord = new Discord([
			'token' => $_ENV['BOT_TOKEN'],
		]);

		$discord->on('ready', function ($discord) {
			echo "Bot is ready!", PHP_EOL;
			$discord->on('message', function ($message, $discord) {
				echo "{$message->author->username}: {$message->content}",PHP_EOL;
			});
		});

		$discord->run();
	}
}