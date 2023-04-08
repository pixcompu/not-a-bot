<?php

namespace app\commands;

use util\Debug;
use util\Storage;
use util\Text;

class Decode extends Command
{
	/**
	 * Decodes a message only if the user provided the right password
	 */
	public function execute(): void
	{
		if($this->options['secret']['value'] !== $_ENV['DECODE_PASSWORD']){
			$this->reply(tt('command.decode.restricted'), true);
			return;
		}

		// get the decoded content of this hash
		$hash = $this->options['key']['value'];
		if(filter_var($hash, FILTER_VALIDATE_URL)){
			// the encode command generate giphy links with a _hash query param appended to them
			// if we receive the full URL to decode we extract only the value of _hash from the link
			$query = parse_url($hash, PHP_URL_QUERY);
			$queryResult = [];

			// parse the query string of the encoded URL (an example URL could be giphy.com/1221233123?_hash=1234567)
			parse_str($query, $queryResult);

			// only if the hash is present we use it, if not we use the full query as the hash so it will fail
			if(isset($queryResult['_hash'])){
				$hash = $queryResult['_hash'];
			}
		}

		$storage = Storage::getInstance();

		Debug::log('getting content of ' . $hash . ' from encoded collection on guild: ' . $this->interaction->guild_id);
		// get a custom response in particular
		$response = $storage->getResponse(
			$this->interaction->guild_id,
			$hash,
			'encoded'
		);
		Debug::log($response);

		// validate that the code exists
		if(!isset($response) || !isset($response['value'])){
			$this->reply(tt('command.decode.missing'), true);
			return;
		}
		$value = $response['value'];

		// to show the decoded content we want to keep it for many seconds so many people can see the decoded content
		$autodestroySeconds = $_ENV['DECODE_AUTODESTROY_SECONDS'];

		// send the decoded content and destroy it in some seconds, so it remains encoded
		$this->reply(tt('command.decode.done'), true);
		$this->postTemporalMessage(
			sprintf(
				tt('command.decode.success'),
				Text::code(($this->interaction->user->username ?? '')),
				Text::code($hash),
				$autodestroySeconds
			),
			$autodestroySeconds
		)->then(function() use($value, $autodestroySeconds){
			$this->postTemporalMessage($value, $autodestroySeconds);
		});
	}
}