<?php

namespace app\commands;

use Discord\Discord;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Interaction;
use Kreait\Firebase\Exception\DatabaseException;
use util\Storage;

class Response extends InteractiveCommand
{
	private Storage $storage;

	public function __construct(Discord $discord, Interaction $interaction)
	{
		parent::__construct($discord, $interaction);
		$this->storage = Storage::getInstance();
	}

	public function execute(): void
	{
		// the message only have the reference to the channel, but with the channel we can get the guild
		$keyword = $this->options['key']['value'];
		$content = $this->options['content']['value'] ?? null;
		// we could use the same command to save and retrieve custom bot messages
		if($content) {
			$this->saveResponse($keyword, $content);
		} else {
			$this->showResponse($keyword);
		}
	}

	/**
	 * Saves the response in the DB
	 * @param $keyword
	 * @param $content
	 * @return void
	 * @throws DatabaseException
	 */
	private function saveResponse($keyword, $content)
	{
		// set a new custom response for a specific keyword
		// if the keyword was defined previously it will be overwritten, so be careful
		$this->storage->setResponse(
			$this->interaction->guild_id,
			$keyword,
			$content
		);
		$this->reply(sprintf(tt('command.response.set'), $keyword));
	}

	/**
	 * Show the response selected in the channel
	 * @param $keyword
	 * @throws DatabaseException
	 */
	private function showResponse($keyword)
	{
		// get a custom response in particular
		$response = $this->storage->getResponse(
			$this->interaction->guild_id,
			$keyword
		);
		// we could have 2 outcomes
		// the response is set and can be returned
		if(isset($response)){
			$this->reply($response['value']);
		} else {
			// we didn't find a response for this keyword
			$this->reply(tt('command.response.missing'), true);
		}
	}

	/**
	 * @param $text
	 * @return void
	 */
	public function autocomplete($text): void
	{
		$keys = $this->storage->getAllKeys($this->interaction->guild_id);
		$choices = [];
		foreach ($keys as $key){
			if(strpos(strtolower($key), strtolower($text)) !== false){
				$choices[] = Choice::new($this->discord, $key, $key);
			}
		}
		$this->interaction->autoCompleteResult($choices);
	}

	/**
	 * @return void
	 */
	public function interact(): void
	{
		$keyword = $this->interaction->data->options->get('name', 'key')->value ?? null;
		if(isset($keyword)){
			$this->autocomplete($keyword);
		}
	}
}