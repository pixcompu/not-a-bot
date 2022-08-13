<?php

namespace app\commands;
use Discord\Discord;
use Discord\Parts\Channel\Message;

abstract class Command
{
	/**
	 * @var Discord
	 */
	protected Discord $botDiscord;

	/**
	 * @var Discord
	 */
	protected Discord $messageDiscord;

	/**
	 * @var Message - the message that triggered this command
	 */
	protected Message $message;

	/**
	 * @var array - the array of additional arguments passed with the command
	 */
	protected array $args;

	/**
	 * Command constructor.
	 * @param Discord $messageDiscord
	 * @param Discord $botDiscord
	 * @param Message $message
	 * @param array $args
	 */
	public function __construct(Discord $botDiscord, Discord $messageDiscord, Message $message, array $args = [])
	{
		$this->messageDiscord = $messageDiscord;
		$this->botDiscord = $botDiscord;
		$this->message = $message;
		$this->args = $args;
	}

	/**
	 * The function that will trigger
	 * @return mixed
	 */
	public abstract function execute() : void;

	/**
	 * @param string $content
	 * @param int $autoDestructSeconds
	 * @param boolean $isReply
	 */
	protected function sendTimedMessage($content, $autoDestructSeconds, $isReply = false)
	{
		$message = null;
		if($isReply){
			$message = $this->message->reply($content);
		} else {
			$message = $this->message->channel->sendMessage($content);
		}
		$message->then(function(Message $message)use($autoDestructSeconds){
			$this->botDiscord->getLoop()->addTimer($autoDestructSeconds, function() use ($message){
				$message->delete();
			});
		});
		return $message;
	}
}