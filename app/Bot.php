<?php

namespace app;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use ReflectionClass;
use util\Storage;

class Bot
{
	/**
	 * @var Discord - main object to interact with discord API
	 */
	private Discord $botDiscord;

	/**
	 * @var array[]
	 */
	private array $commands;

	/**
	 * Bot constructor.
	 * @throws \Discord\Exceptions\IntentException
	 * @throws \Exception
	 */
	public function __construct()
	{
		// parse commands from stand alone file, we will configure the commands in their own separate file so we can access them
		// from all places where we want them
		$this->commands = require(__DIR__ . '/commands.php');

		// initialize discord bot, we only need to provider our secret token
		$options = [
			'token' => $_ENV['BOT_TOKEN'],
		];
		$this->botDiscord = new Discord($options);

		// validate that our list of commands doesn't have collisions
		$allKeywords = array_merge(...array_column($this->commands, 'keywords'));
		$occurrenceByKeyword = array_count_values($allKeywords);
		$keywordsWithMultipleOccurrences = array_keys(array_diff($occurrenceByKeyword, [1]));
		if(count($keywordsWithMultipleOccurrences) > 0){
			throw new \Exception('Two commands cant share the same keyword, repeated keywords found: ' . implode(',', $keywordsWithMultipleOccurrences));
		}
	}

	/**
	 * initialize the discord listener
	 */
	public function execute()
	{
		$this->botDiscord->on('ready', \Closure::fromCallable([$this, 'ready']));
		$this->botDiscord->run();
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
		$this->botDiscord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $userDiscord) {
			$this->analyze($message, $userDiscord);
		});

		$this->botDiscord->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
			// we need to validate that the same user that triggered the message is the same that is interacting
			if($interaction->user->id === $interaction->user->id){
				// in the custom id we saved the classname so we invoke the class with that name
				$class = explode('|', $interaction->data->custom_id)[0] ?? null;
				if(isset($class)){
					try{
						$class = new ReflectionClass($class);
						$instance = $class->newInstanceArgs([
							$this->botDiscord,
							$discord,
							$interaction->message,
							[]
						]);
						// and execute the interact method
						$instance->interact($interaction);
					} catch(\Throwable $ex){
						$interaction->message->reply(tt('command.general.error.instance'));
					}
				} else {
					$interaction->message->reply(tt('command.general.error.instance'));
				}
			}

			// we need to acknowledge the interaction so discord know that we processed it
			$interaction->acknowledge();
		});

		$this->botDiscord->on(Event::GUILD_CREATE, function (Guild $guild){
			Storage::getInstance()->setGuild($guild->id, $guild->name);
		});
	}

	/**
	 * analyze the message to see if it should trigger a command
	 * @param Message $message
	 * @param Discord $userDiscord
	 * @throws \Exception
	 */
	private function analyze(Message $message, Discord $userDiscord)
	{
		// message was from the same bot, ignore, if the bot answers with a message that could trigger the bot again
		// we could end in a infinite loop
		if(isset($message->author->user->bot) && $message->author->user->bot) return;

		// determine if this intended to be direct call to the bot
		// we will only consider that is a intended bot call if the preffix is the first caracter of the message
		$isUsingPrefix = ($message->content[0] ?? '') === $_ENV['COMMAND_PREFIX'];
		if($isUsingPrefix){
			// if we got a message intended to be a command, then we will parse dynamically the command key to invoke a class
			$content = str_replace($_ENV['COMMAND_PREFIX'], '', $message->content);
			if(strlen($content) > 0){
				// allow multiple spaces between command arguments
				$commandPieces = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
				$command = $this->getCommandByKeyword(array_shift($commandPieces));
				// if we got a valid command, instantiate the command class to handle it
				if(isset($command)) {
					try{
						$class = new ReflectionClass($command['namespace'] . '\\' . $command['class']);
						$instance = $class->newInstanceArgs([
							$this->botDiscord,
							$userDiscord,
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
	 * Make sure the discord listener isn't alive when stopping the entrypoint process
	 * otherwise multiple listeners will be alive and the bot will reply to ever message multiple times
	 */
	public function __destruct()
	{
		if(isset($this->botDiscord)){
			$this->botDiscord->close();
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