<?php

namespace app\commands;

use util\Gif;
use util\Storage;
use Discord\Parts\Channel\Message;

class Encode extends Command
{

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
		$gifUrl = Gif::getInstance()->random('animal');
		if(parse_url($gifUrl, PHP_URL_QUERY)){
			$finalUrl = $gifUrl . '&';
		} else {
			$finalUrl = $gifUrl . '?';
		}

		// send the actual message to the channel
		$encodedQuery = http_build_query(
			[
				'_hash' => $hash
			]
		);
		$finalUrl = $finalUrl . $encodedQuery;

		// if we just reply, the slash command will be shown in the channel history, because each slash command have a response
		// so what we do is to answer the slash command and then delete the full interaction, and post a new message outside the interaction
		$this->postOnChannel($finalUrl)->then(function(Message $message){
			$message->react('😉');
		});
	}
}