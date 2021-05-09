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
		$args = $this->args;
		
		// delete the message as soon as possible because it's sensitive
		$this->message->delete();

		// for errors we don't want to keep them for too long in the channel because they will be seen right away by the author
		$errorAutodestroySeconds = 10;

		// validate that we got something to encode
		if(empty($args)){
			$this->sendTimedMessage(tt('command.encode.empty'), $errorAutodestroySeconds, true);
			return;
		}

		// generate a unique id to assign to this content
		$hash = uniqid();

		// get the channel to scope the encoded content by channel
		$channel = $this->messageDiscord->getChannel($this->message->channel_id);

		// the value will be all the text after the command
		$value = implode(' ', $args);

		// save the content to the encoded collection of the database
		$storage = Storage::getInstance();
		$storage->setResponse(
			$channel->guild_id,
			$hash,
			$value,
			'encoded'
		);

		// send the hash assigned to this message with a random GIF, so it can be decoded anytime
		$this->sendEncodedMessage($hash);
	}

	/**
	 * Sends a hash to the channel with a random GIF
	 * @param $hash
	 */
	public function sendEncodedMessage($hash){
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
				$this->message->channel->sendMessage($finalUrl)->then(function(Message $message){
					$message->react('😉');
				});
			})->otherwise(function() use($encodedQuery, $defaultGifUrl){
				// send the actual message to the channel (we don't care if the giphy API works, we send the default GIF if that happens)
				$this->message->channel->sendMessage($defaultGifUrl . '?' . $encodedQuery)->then(function(Message $message){
					$message->react('😉');
				});;
			});
		$promise->wait();
	}
}