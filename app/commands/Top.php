<?php

namespace app\commands;

use Discord\Helpers\Collection;
use util\Text;

class Top extends Command
{

	/**
	 * gives the most sent messages in a channel (based on the last 100 messages)
	 */
	public function execute(): void
	{
		$limit = 5;
		if(isset($this->args[0]) && is_numeric($this->args[0])){
			$limit = intval($this->args[0]);
		}

		// get the guild where the bot was called from the db
		$this->message->channel->getMessageHistory([
			'before' => $this->message
		])->then(function (Collection $messages) use($limit){
			$this->showTopMessages($messages, $limit);
		});
	}

	/**
	 * calculates and shows the list of most sent messages in the channel
	 * @param Collection $messages
	 * @param int $limit
	 * @return void
	 * @throws \Exception
	 */
	private function showTopMessages(Collection $messages, int $limit) : void
	{
		// first we will get a list of raw messages so we can use PHP built in functions to count
		$countByMessage = array_reduce($messages->toArray(), function ($map, $message) {
			// if out bot or another bot is the author of the message, discard it
			$isBot = $message->author->bot || $this->botDiscord->user->id === $message->user_id;
			// if the message it's used to chat with another person (contains mentions), discard it
			$isMention = count($message->mentions) > 0;
			// set a min lenght of 5 just so very small repetitive messages don't get counted
			if (!$isBot && !$isMention && strlen($message->content) > 5) {
				// remove the message case and special characters like diacritics
				$comparableMessage = Text::unaccent(trim(strtolower($message->content)));
				if(!isset($map[$comparableMessage])){
					$map[$comparableMessage] = [
						'messages' => [],
						'count' => 0
					];
				}
				// always get latest message as sample
				$map[$comparableMessage]['messages'][] = $message;
				$map[$comparableMessage]['count']++;
			}
			return $map;
		}, []);

		// return only occurrences greater that one (message was sent only once so can't be a top message)
		$countByMessageValues = array_values($countByMessage);
		$messageCounts = array_filter($countByMessageValues, function($messageCount){
			return $messageCount['count'] > 1;
		});

		// sort occurrences from greatest to lowest
		usort($messageCounts, function($a, $b){
			return $b['count'] - $a['count'];
		});

		// process each message in the count relation
		$lines = [];
		$lines[] = $this->bold(tt('command.top.start') . ':');
		// calculate if the actual results are less than the limit requested, then return only as much results as we can calculate
		// otherwise return the same number of elements the limit is requesting
		$min = min($limit, count($messageCounts));
		for($i = 0; $i < $min; $i++){
			$messageCount = $messageCounts[$i];
			// get the last message that repeated this text
			$last = $messageCount['messages'][count($messageCount['messages']) - 1];

			$lines[] = sprintf('> %s (%d %s, %s %s)',
				$this->code($last->content),
				$messageCount['count'],
				tt('command.top.item_times'),
				tt('command.top.item_by'),
				$this->bold($last->author->username)
			);
		}

		// if we found at least one top command output all
		if (count($lines) > 1) {
			$response = array_reduce($lines, function ($text, $line) {
				return $text . $line . PHP_EOL;
			}, '');
			$this->message->channel->sendMessage($response);
		} else {
			// otherwise reply
			$this->message->reply(tt('command.top.end_empty'));
		}
	}
}