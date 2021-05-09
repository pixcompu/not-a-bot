<?php

namespace app\commands;

use util\Storage;
use util\Text;

class Decode extends Command
{
	/**
	 * Decodes a message only if the user provided the right password
	 */
	public function execute(): void
	{
		// for errors we don't want to keep them for too long in the channel because they will be seen right away by the author
		$errorAutodestroySeconds = 10;
		$args = $this->args;
		
		// delete the message as soon as possible because it probably contains the password to decode 
		$this->message->delete();

		// validate that we have at least 2 arguments to work on the decodification
		if(count($args) < 2){
			$this->sendTimedMessage(tt('command.decode.wrong'), $errorAutodestroySeconds, true);
			return;
		}

		// validate that the password is correct
		$password = trim($args[1]);
		if($password !== $_ENV['DECODE_PASSWORD']){
			$this->sendTimedMessage(tt('command.decode.restricted'), $errorAutodestroySeconds, true);
			return;
		}

		// get the decoded content of this hash
		$hash = $args[0];
		$channel = $this->messageDiscord->getChannel($this->message->channel_id);
		$storage = Storage::getInstance();

		// get a custom response in particular
		$response = $storage->getResponse(
			$channel->guild_id,
			$hash,
			'encoded'
		);

		// validate that the code exists
		if(!isset($response)){
			$this->sendTimedMessage(tt('command.decode.missing'), $errorAutodestroySeconds, true);
			return;
		}

		// to show the decoded content we want to keep it for many seconds so many people can see the decoded content
		$autodestroySeconds = $_ENV['DECODE_AUTODESTROY_SECONDS'];
		
		// send the decoded content and destroy it in some seconds, so it remains encoded
		$this->sendTimedMessage(sprintf(tt('command.decode.success'), Text::code($hash), $autodestroySeconds), $autodestroySeconds)->then(function() use($response, $autodestroySeconds){
			$this->sendTimedMessage($response['value'], $autodestroySeconds);
		});
	}
}