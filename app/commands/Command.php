<?php

namespace app\commands;
use Discord\Discord;
use Discord\Parts\Channel\Message;

abstract class Command
{
	/**
	 * @var Discord
	 */
	protected Discord $discord;

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
	 * @param Discord $discord
	 * @param Message $message
	 * @param $args
	 */
	public function __construct(Discord $discord, Message $message, $args)
	{
		$this->discord = $discord;
		$this->message = $message;
		$this->args = $args;
	}

	/**
	 * The function that will trigger
	 * @return mixed
	 */
	public abstract function execute() : void;
}