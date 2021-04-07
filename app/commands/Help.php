<?php


namespace app\commands;


class Help extends Command
{

	/**
	 * Get the help text for all the commands
	 * @throws \Exception
	 */
	public function execute(): void
	{
		// get the commands information from the configuration file
		$commands = require(__DIR__ . '/../commands.php');

		// we will save a list of lines to build the final text, this is to avoid putting PHP_EOL at the end of each sentence
		$helpLines = [];

		// set the initial help text
		$helpLines[] = tt('command.help.start') . ':';

		// for each command we want to show their name, description, ways to call it and the arguments that can receive
		foreach ($commands as $command) {
			// name and description of the command
			$helpLines[] = $this->underline($this->bold($command['name'] . ' (' . $command['description'] . ')'));

			// ways to call this command (aka command aliases)
			$helpLines[] = tt('command.help.keywords_start') . ':';
			foreach ($command['keywords'] as $keyword){
				$helpLines[] = '- ' . $_ENV['COMMAND_PREFIX'] . $this->italic($keyword);
			}

			// for the arguments we will take the first of the possible ways to call the command
			$sampleKeyword = $_ENV['COMMAND_PREFIX'] . $command['keywords'][0];
			$helpLines[] = tt('command.help.usage_start') . ':';

			// we intentionally separed the usages by commas on the translation file, to be able to show each of them on a separate line
			// doing a split by the comma
			$usages = explode(',', $command['usage']);
			foreach ($usages as $usage) {
				$helpLines[] = $this->code($sampleKeyword . ' ' . trim($usage));
			}

			// this is to add a breakline at the end of each command
			$helpLines[] = '';
		}

		// to every line add a breakline and build the full text that we will use to reply to the user
		$helpText = array_reduce($helpLines, function($help, $line){
			return $help . $line . PHP_EOL;
		}, '');
		$this->message->reply($helpText);
	}
}