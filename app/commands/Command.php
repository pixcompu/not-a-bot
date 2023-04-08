<?php

namespace app\commands;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use React\Promise\ExtendedPromiseInterface;

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

	/**
	 * @var Channel
	 */
	private Channel $channel;

	/**
	 * @var bool
	 */
	private bool $replied;

	/**
	 * Command constructor.
	 * @param Discord $discord
	 * @param Interaction $interaction
	 */
	public function __construct(Discord $discord, Interaction $interaction)
	{
		$this->interaction = $interaction;
		$this->discord = $discord;
		$this->channel = $this->discord->getChannel($interaction->channel->id);
		$this->options = json_decode(json_encode((object)$interaction->data->options->toArray()), true);
		$this->replied = false;
	}

	/**
	 * The function that will trigger
	 * @return mixed
	 */
	public abstract function execute() : void;

	/**
	 * Reply to the original slash command
	 * @param $content
	 * @param bool $ephemeral
	 * @return ExtendedPromiseInterface
	 */
	protected function reply($content, bool $ephemeral = false){
		// we could respond with an Embed or Components, so we first detect if the content is already a custom object
		// the reply methods expect a Builder object always
		if($content instanceof MessageBuilder){
			$finalMessage = $content;
		} else {
			$finalMessage = MessageBuilder::new()->setContent($content);
		}
		// we can't reply twice to the same interaction so instead of throwing an error if it happens
		// we update the original reply that we did to the slash command
		if($this->replied){
			return $this->interaction->updateOriginalResponse(
				$finalMessage
			);
		} else {
			// branch where we haven't replied to the slash command yet
			$this->replied = true;
			return $this->interaction->respondWithMessage(
				$finalMessage,
				$ephemeral
			);
		}
	}

	/**
	 * Puts a message on the channel where the original interaction was detected
	 * @param $content
	 * @return ExtendedPromiseInterface
	 * @throws NoPermissionsException
	 */
	protected function postMessage($content){
		return $this->channel->sendMessage($content);
	}

	/**
	 * @param string $content
	 * @param int $autoDestructSeconds
	 * @throws NoPermissionsException
	 */
	protected function postTemporalMessage($content, int $autoDestructSeconds)
	{
		return $this->postMessage($content)->then(
			function (Message $message) use ($autoDestructSeconds) {
				$this->discord->getLoop()->addTimer($autoDestructSeconds, function () use ($message) {
					$message->delete();
				});
			}
		);
	}
}