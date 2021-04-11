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
	 * @param $args
	 */
	public function __construct(Discord $messageDiscord, Discord $botDiscord, Message $message, $args)
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
	 * Return a bold formatted text
	 * @param $text
	 * @return string
	 */
	protected function bold($text)
	{
		return '**' . $text . '**';
	}

	/**
	 * Return a italic formatted text
	 * @param $text
	 * @return string
	 */
	protected function italic($text)
	{
		return '_' . $text . '_';
	}

	/**
	 * Return a underlined text
	 * @param $text
	 * @return string
	 */
	protected function underline($text)
	{
		return '__' . $text . '__';
	}

	/**
	 * Return a code like text
	 * @param $text
	 * @return string
	 */
	protected function code($text)
	{
		return '`' . $text . '`';
	}
}