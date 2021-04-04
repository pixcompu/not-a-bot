<?php

namespace app\commands;

class Meow extends Command
{

	private $randomCatsApiUrl = "https://api.thecatapi.com/v1/images/search";

	/**
	 * reply to the user with a random cat image
	 */
	public function execute(): void
	{
		// get a random image of a cat from the cat api
		$client = new \GuzzleHttp\Client();
		$promise = $client
			->getAsync($this->randomCatsApiUrl)
			->then(function($response){
				$body = json_decode($response->getBody(), true);
				$this->message->reply(
					tt('command.kitten.success') . ' ' . $body[0]['url']
				);
			});
		$promise->wait();
	}
}