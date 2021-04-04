<?php

namespace app\commands;
use Discord\Parts\Channel\Message;

abstract class Command
{
	/**
	 * @var Message - the message that triggered this command
	 */
	protected $message;

	/**
	 * @var array - the array of additional arguments passed with the command
	 */
	protected $args;

	/**
	 * Command constructor.
	 * @param $args
	 * @param Message $message
	 */
	public function __construct($args, Message $message)
	{
		$this->message = $message;
		$this->args = $args;
	}

	/**
	 * The function that will trigger
	 * @return mixed
	 */
	public abstract function execute() : void;
}