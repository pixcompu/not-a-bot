<?php

namespace app;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use ReflectionClass;
use util\Debug;
use util\Storage;
use function React\Promise\all;

class Bot
{
	/**
	 * @var Discord - main object to interact with discord API
	 */
	private Discord $discord;

	/**
	 * Bot constructor.
	 * @throws \Discord\Exceptions\IntentException
	 * @throws \Exception
	 */
	public function __construct()
	{
		// initialize discord bot, we only need to provider our secret token
		$options = [
			'token' => $_ENV['BOT_TOKEN'],
		];
		$this->discord = new Discord($options);
	}

	/**
	 * initialize the discord listener
	 */
	public function execute()
	{
		$this->discord->on('ready', \Closure::fromCallable([$this, 'ready']));
		$this->discord->on('error', \Closure::fromCallable([$this, 'error']));
		$this->discord->run();
	}

	/**
	 * configure any action that need to be executed when the bot just loaded
	 */
	private function ready()
	{
		// we will define if we want to first delete all commands before upserting the ones we have in the code
		if($_ENV['SET_UP_COMMANDS_ON_START']){
			$this->pruneAllCommands()->then(function($results) {
				Debug::log('deleted ' . count($results) . ' commands');
				$this->setUpStaticCommands()->then(function($results){
					Debug::log('created ' . count($results) . ' commands');
					Debug::log(tt('setup.ready'));
					$this->listen();
				});
			});
		} else {
			$this->listen();
		}
	}


	private function error($error)
	{
		Debug::log('A general error has occurred');
		Debug::log($error);
	}

	private function pruneAllCommands()
	{
		// clear all commands because we will submit them on the next step
		// this ensures that we don't have in discord any command that we don't have in our code
		return $this->discord->application->commands->freshen()->then(function($commandMap) {
			$promises = [];
			foreach ($commandMap as $key => $command){
				Debug::log('deleting command ' . $key . ' name: ' . $command->name);
				$promises[] = $this->discord->application->commands->delete($command);
			}
			return all($promises);
		});
	}

	/**
	 * configure every event we want to listen from discord
	 */
	private function listen()
	{
		$commandKeys = array_keys($this->getStaticCommandDefinitions());
		foreach ($commandKeys as $commandKey) {
			Debug::log('listening to slash command >' . $commandKey);
			$this->discord->listenCommand($commandKey, function (Interaction $interaction) use ($commandKey){
				Debug::log('trying to process command >' . $commandKey);
				$this->processStaticCommand($commandKey, $interaction);
			});
		}
		$this->discord->on(Event::GUILD_CREATE, function (Guild $guild){
			Storage::getInstance()->setGuild($guild->id, $guild->name);
		});
		$this->discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction) {
			// in the custom id we saved the classname so we invoke the class with that name
			Debug::log('received interaction');
			Debug::log($interaction);
			$class = explode('|', $interaction->data->custom_id)[0] ?? null;
			if (isset($class)) {
				try {
					$class = new ReflectionClass($class);
					$instance = $class->newInstanceArgs([
						$this->discord,
						$interaction
					]);
					// and execute the interact method
					$instance->interact();
				} catch (\Throwable $ex) {
					$interaction->message->reply(tt('command.general.error.instance'));
				}
			} else {
				$interaction->message->reply(tt('command.general.error.instance'));
			}
			// we need to acknowledge the interaction so discord know that we processed it
			$interaction->acknowledge();
		});
		Debug::log(tt('setup.ready'));
	}

	/**
	 * Make sure the discord listener isn't alive when stopping the entrypoint process
	 * otherwise multiple listeners will be alive and the bot will reply to ever message multiple times
	 */
	public function __destruct()
	{
		if(isset($this->discord)){
			$this->discord->close();
		}
	}

	private function getStaticCommandDefinitions()
	{
		return [
			'meow' => [
				'class' => \app\commands\Meow::class
			],
			'd1' => [
				'class' => \app\commands\D1::class,
				'options' => [
					[
						'name' => 'game',
						'description' => 'Game to play',
						'type' => Option::STRING,
						'required' => true
					]
				]
			],
			'music' => [
				'class' => \app\commands\Music::class,
			],
			'catalog' => [
				'class' => \app\commands\Catalog::class,
			],
			'top' => [
				'class' => \app\commands\Top::class,
				'options' => [
					[
						'name' => 'number',
						'description' => 'Number of results to show',
						'type' => Option::INTEGER,
						'required' => true
					]
				]
			],
			'encode' => [
				'class' => \app\commands\Encode::class,
				'options' => [
					[
						'name' => 'content',
						'description' => 'Content to encode',
						'type' => Option::STRING,
						'required' => true
					]
				]
			],
			'decode' => [
				'class' => \app\commands\Decode::class,
				'options' => [
					[
						'name' => 'key',
						'description' => 'Content to decode',
						'type' => Option::STRING,
						'required' => true
					],
					[
						'name' => 'secret',
						'description' => 'Passphrase to decode',
						'type' => Option::STRING,
						'required' => true
					]
				]
			],
			'response' => [
				'class' => \app\commands\Response::class,
				'options' => [
					[
						'name' => 'key',
						'description' => 'Key to show',
						'type' => Option::STRING,
						'required' => true
					],
					[
						'name' => 'content',
						'description' => 'Content to associate to the key',
						'type' => Option::STRING,
						'required' => false
					]
				]
			]
		];
	}

	private function setUpStaticCommands() {
		$commandConfigurations = $this->getStaticCommandDefinitions();
		$promises = [];
		foreach($commandConfigurations as $key => $config){
			$description = tt('command.' . $key . '.description');
			$rawCommand = CommandBuilder::new()
				->setName($key)
				->setDescription($description);
			foreach($config['options'] ?? [] as $option){
				$rawCommand->addOption(
					(new Option($this->discord))
						->setName($option['name'])
						->setDescription($option['description'])
						->setType($option['type'])
						->setRequired($option['required'])
				);
			}
			Debug::log('creating command ' . $key);
			$command = $this->discord->application->commands->create($rawCommand->toArray());
			$promises[] = $this->discord->application->commands->save($command);
		}
		return all($promises);
	}

	private function processStaticCommand($key, Interaction $interaction)
	{
		$config = $this->getStaticCommandDefinitions()[$key];
		try {
			$class = new ReflectionClass($config['class']);
			$instance = $class->newInstanceArgs([
				$this->discord,
				$interaction
			]);
			$instance->execute();
		} catch(\Throwable $ex){
			Debug::log($ex->getFile() . '(' . $ex->getLine() . ') ' . $ex->getMessage());
			Debug::log($ex->getTraceAsString());
			$interaction->respondWithMessage(MessageBuilder::new()->setContent(tt('command.general.error.instance')));
		}
	}
}