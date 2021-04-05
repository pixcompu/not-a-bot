<?php


namespace app\commands;


use util\Storage;

class Response extends Command
{
	public function execute(): void
	{
		$storage = Storage::getInstance();
		// we could use the same command to save and retrieve custom bot messages
		if(count($this->args) > 1){
			// set a new custom response for a specific keyword
			// if the keyword was defined previously it will be overwritten, so be careful
			$storage->setResponse(
				$this->message->author->guild->id,
				$this->args[0],
				$this->args[1]
			);
			$this->message->reply(sprintf(tt('command.response.set'), $this->args[0]));
		} else {
			// get a custom response
			$response = $storage->getResponse(
				$this->message->author->guild->id,
				$this->args[0]
			);
			// we could have 2 outcomes
			// the response is set and can be returned
			if(isset($response)){
				$this->message->reply($response['value']);
			} else {
				// we didn't find a response for this keyword
				$this->message->reply(tt('command.response.missing'));
			}
		}
	}
}