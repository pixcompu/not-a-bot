<?php


namespace app\commands;


use util\Storage;

class Response extends Command
{
	public function execute(): void
	{
		// the message only have the reference to the channel, but with the channel we can get the guild
		$channel = $this->discord->getChannel($this->message->channel_id);

		$args = $this->args;
		$storage = Storage::getInstance();
		// we could use the same command to save and retrieve custom bot messages
		if(empty($this->args)) {
			// show all responses saved
			$responses = $storage->getAllResponses($channel->guild_id) ?? [];
			$reply = tt('command.response.list') . ':' . PHP_EOL;
			foreach($responses as $keyword => $response){
				$reply .= $keyword . PHP_EOL;
			}
			$this->message->reply($reply);
		} elseif (count($this->args) === 1) {
			$keyword = array_shift($args);
			// get a custom response in particular
			$response = $storage->getResponse(
				$channel->guild_id,
				$keyword
			);
			// we could have 2 outcomes
			// the response is set and can be returned
			if(isset($response)){
				$this->message->channel->sendMessage($response['value']);
			} else {
				// we didn't find a response for this keyword
				$this->message->reply(tt('command.response.missing'));
			}
		} else {
			$keyword = array_shift($args);
			$value = implode(' ', $args);
			// set a new custom response for a specific keyword
			// if the keyword was defined previously it will be overwritten, so be careful
			$storage->setResponse(
				$channel->guild_id,
				$keyword,
				$value
			);
			$this->message->reply(sprintf(tt('command.response.set'), $keyword));
		}
	}
}