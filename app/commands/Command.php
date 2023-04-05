<?php

namespace app\commands;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;

abstract class Command
{
	/**
	 * @var Discord
	 */
	protected Discord $discord;

	/**
	 * @var Interaction
	 */
	protected Interaction $interaction;

	/**
	 * @var array|mixed
	 */
	protected array $options;

	private $channel;

	private $replied = false;

	/**
	 * Command constructor.
	 * @param Discord $discord
	 * @param Interaction $interaction
	 */
	public function __construct(Discord $discord, Interaction $interaction)
	{
		$this->interaction = $interaction;
		$this->channel = $interaction->channel;
		$this->discord = $discord;
		$this->options = json_decode(json_encode((object)$interaction->data->options->toArray()), true);
	}

	/**
	 * The function that will trigger
	 * @return mixed
	 */
	public abstract function execute() : void;

	/**
	 * @param string $content
	 * @param int $autoDestructSeconds
	 */
	protected function sendTimedMessage($content, $autoDestructSeconds)
	{
		return $this->postOnChannel($content)->then(function(Message $message)use($autoDestructSeconds){
			$this->discord->getLoop()->addTimer($autoDestructSeconds, function() use ($message){
				$message->delete();
			});
		});
	}

	protected function reply($content, $ephemeral = false){
		if($content instanceof MessageBuilder){
			$finalMessage = $content;
		} else {
			$finalMessage = MessageBuilder::new()->setContent($content);
		}
		if($this->replied){
			return $this->interaction->updateOriginalResponse(
				$finalMessage
			);
		} else {
			$this->replied = true;
			return $this->interaction->respondWithMessage(
				$finalMessage,
				$ephemeral
			);
		}
	}

	protected function postOnChannel($content){
		return $this->channel->sendMessage($content);
	}
}