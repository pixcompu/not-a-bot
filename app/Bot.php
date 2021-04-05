<?php

namespace app;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use ReflectionClass;

class Bot
{
	/**
	 * @var Discord - main object to interact with discord API
	 */
	private Discord $discord;

	/**
	 * @var array[]
	 */
	private array $commands = [
		[
			'name' => 'Random Cat',
			'class' => 'Meow',
			'namespace' => '\\app\\commands',
			'keywords' => ['kitten', 'cat', 'miau', 'meow']
		]
	];

	/**
	 * Bot constructor.
	 * @throws \Discord\Exceptions\IntentException
	 */
	public function __construct()
	{
		// initialize discord bot, we only need to provider our secret token
		$options = [
			'token' => $_ENV['BOT_TOKEN'],
		];
		$this->discord = new Discord($options);

		// validate that our list of commands doesn't have collisions
		$allKeywords = array_merge(...array_column($this->commands, 'keywords'));
		$occurrenceByKeyword = array_count_values($allKeywords);
		$keywordsWithMultipleOccurrences = array_keys(array_diff($occurrenceByKeyword, [1]));
		if(count($keywordsWithMultipleOccurrences) > 0){
			throw new \Exception('Two commands cant share the same keyword, repeated keywords found: ' . implode($keywordsWithMultipleOccurrences));
		}
	}

	/**
	 * initialize the discord listener
	 */
	public function execute()
	{
		$this->discord->on('ready', \Closure::fromCallable([$this, 'ready']));
		$this->discord->run();
	}

	/**
	 * configure any action that need to be executed when the bot just loaded
	 */
	private function ready()
	{
		echo tt('setup.ready'), PHP_EOL;
		$this->listen();
	}

	/**
	 * configure every event we want to listen from discord
	 */
	private function listen()
	{
		$this->discord->on(Event::MESSAGE_CREATE, function (Message $message) {
			$this->analyze($message);
		});
	}

	/**
	 * analyze the message to see if it should trigger a command
	 * @param Message $message
	 * @throws \Exception
	 */
	private function analyze(Message $message)
	{
		// message was from the same bot, ignore, if the bot answers with a message that could trigger the bot again
		// we could end in a infinite loop, we will allow to process messages from other bots though
		if($message->author->user->id === $this->discord->user->id) return;

		// determine if this intended to be direct call to the bot
		// we will only consider that is a intended bot call if the preffix is the first caracter of the message
		$isUsingPrefix = ($message->content[0] ?? '') === $_ENV['COMMAND_PREFIX'];
		if($isUsingPrefix){
			// if we got a message intended to be a command, then we will parse dynamically the command key to invoke a class
			$content = str_replace($_ENV['COMMAND_PREFIX'], '', $message->content);
			if(strlen($content) > 0){
				$commandPieces = explode(' ', $content);
				$command = $this->getCommandByKeyword(array_shift($commandPieces));
				// if we got a valid command, instantiate the command class to handle it
				if(isset($command)) {
					try{
						$class = new ReflectionClass($command['namespace'] . '\\' . $command['class']);
						$instance = $class->newInstanceArgs([
							$this->discord,
							$message,
							$commandPieces
						]);
						$instance->execute();
					} catch(\Throwable $ex){
						echo $ex->getMessage() . PHP_EOL;
						$message->reply(tt('command.general.error.instance'));
					}
				} else {
					$message->reply(tt('command.general.error.missing'));
				}
			} else {
				$message->reply(tt('command.general.error.empty'));
			}
		}
	}

	/**
	 * @param $requestedCommandKeyword
	 * @return array|null
	 */
	private function getCommandByKeyword($requestedCommandKeyword): ?array
	{
		foreach ($this->commands as $command){
			foreach ($command['keywords'] as $keyword){
				if($keyword === $requestedCommandKeyword){
					return $command;
				}
			}
		}
		return null;
	}
}