<?php

namespace app\commands;

use util\Storage;
use GuzzleHttp\Client;
use Discord\Parts\Channel\Message;

class Encode extends Command
{
	/**
	 * url of the giphy API to get those GIF images
	 * @var string
	*/
	private string $giphyUrl = "https://api.giphy.com/v1/gifs/random";

	/**
	 * Encodes a message assigning to it a identifier so only a user with the password can open it later
	 */
	public function execute(): void
	{
		// generate a unique id to assign to this content
		$hash = uniqid();

		// save the content to the encoded collection of the database
		$storage = Storage::getInstance();
		$storage->setResponse(
			$this->interaction->guild_id,
			$hash,
			$this->options['content']['value'],
			'encoded'
		);

		// notify user that the encoded message will be posted on the channel shortly
		$this->reply(tt('command.encode.ack'), true);

		// send the hash assigned to this message with a random GIF, so it can be decoded anytime
		$this->processEncodedMessage($hash);
	}

	/**
	 * Sends a hash to the channel with a random GIF
	 * @param $hash
	 */
	public function processEncodedMessage($hash){
		// prepare the api call, giphy allow us to set these arguments
		$query = http_build_query(
			[
				// the developer giphy key
				'api_key' => $_ENV['GIPHY_API_KEY'],
				// switch to avoid get NSFW images
				'rating' => 'g',
				// classification
				'tag' => 'animal'
			]
		);

		// we will make a request to the giphy api
		$defaultGifUrl =  'https://media.tenor.com/images/172d63b92f17fb1d90eb37e64bbee10e/tenor.gif';
		// send the actual message to the channel
		$encodedQuery = http_build_query(
			[
				'_hash' => $hash
			]
		);
		$client = new Client();
		$promise = $client
			->getAsync($this->giphyUrl . '?' . $query)
			->then(function($response)use($encodedQuery, $defaultGifUrl){
				$body = json_decode($response->getBody(), true);
				// the response returned at lest one result
				$gifUrl = $defaultGifUrl;
				if(isset($body['data']['url'])){
					// the response contains up to 10 results, we will take a random one from those
					$gifUrl = $body['data']['url'];
				}

				// check if the giphy url already has query params
				// if it has then we will need to appen our param to those
				// if not then we need to create the query params first
				$finalUrl = $gifUrl;
				if(parse_url($gifUrl, PHP_URL_QUERY)){
					$finalUrl = $finalUrl . '&';
				} else {
					$finalUrl = $finalUrl . '?';
				}
				$finalUrl = $finalUrl . $encodedQuery;
				$this->postMessage($finalUrl);
			})->otherwise(function() use($encodedQuery, $defaultGifUrl){
				// send the actual message to the channel (we don't care if the giphy API works, we send the default GIF if that happens)
				$this->postMessage($defaultGifUrl . '?' . $encodedQuery);
			});
		$promise->wait();
	}

	private function postMessage($url){
		// if we just reply, the slash command will be shown in the channel history, because each slash command have a response
		// so what we do is to answer the slash command and then delete the full interaction, and post a new message outside the interaction
		$this->postOnChannel($url)->then(function(Message $message){
			$message->react('😉');
		});
	}
}