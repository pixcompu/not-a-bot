<?php

namespace app\commands;

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Message;
use util\Debug;
use util\Text;

class Top extends Command
{
	/**
	 * @var array
	 */
	private array $allMessages = [];

	/**
	 * we don't want to get a infinite load of messages, so we set a sanity check on the limit of messages
	 * @var int
	 */
	private int $maxNumberOfMessagesToRetrieve = 2000;

	/**
	 * min time a message has to repeat in order to be considered in the output of the command
	 * @var int
	 */
	private int $minOccurrencesToShowMessage = 3;

	/**
	 * gives the most sent messages in a channel (based on the last 100 messages)
	 * @throws \Exception
	 */
	public function execute(): void
	{
		// only allow up to 20 places
		if(isset($this->options['number']['value'])){
			$limit = intval($this->options['number']['value']);
		} else {
			$this->reply(tt('command.top.wrong_limit'));
			return;
		}

		// get the guild where the bot was called from the db
		$this->reply(tt('command.top.wait'));
		$this->retrieveFullMessageHistory(null, function()use ($limit){
			$this->showTopMessages($limit);
		}, function(){
			$this->reply(tt('command.top.error'));
		});
	}

	/**
	 * calculates and shows the list of most sent messages in the channel
	 * @param int $limit
	 * @return void
	 * @throws \Exception
	 */
	private function showTopMessages(int $limit) : void
	{
		Debug::log('starting message processing');
		// respond to the user that we are halfway there
		$this->reply(sprintf(tt('command.top.middle'), count($this->allMessages)));

		// first we will get a list of raw messages so we can use PHP built in functions to count
		$countByMessage = array_reduce($this->allMessages, function ($map, $message) {
			// if out bot or another bot is the author of the message, discard it
			$isBot = $message->author->bot || $this->discord->user->id === $message->user_id;
			// if the message it's used to chat with another person (contains mentions), discard it
			$isMention = count($message->mentions) > 0;
			// set a min length of 5 just so very small repetitive messages don't get counted
			if (!$isBot && !$isMention && strlen($message->content) > 5) {
				// remove the message case and special characters like diacritics
				$comparableMessage = Text::unaccent(trim(strtolower($message->content)));
				// always get latest message as sample
				$map[$comparableMessage]['last_message'] = (object)[
					'content' => $message->content,
					'author' => $message->author->username
				];
				if(isset($map[$comparableMessage]['count'])){
					$map[$comparableMessage]['count']++;
				} else {
					$map[$comparableMessage]['count'] = 1;
				}
			}
			return $map;
		}, []);

		// return only occurrences greater that one (message was sent only once so can't be a top message)
		$countByMessageValues = array_values($countByMessage);
		$messageCounts = array_values(array_filter($countByMessageValues, function($messageCount){
			return $messageCount['count'] >= $this->minOccurrencesToShowMessage;
		}));
		// sort occurrences from greatest to lowest
		usort($messageCounts, function($a, $b){
			if($b['count'] === $a['count']){
				$lastAText = $a['last_message']->content;
				$lastBText = $b['last_message']->content;
				// to avoid randomize the results when they have the same count
				// we use as second criteria the order by message content
				// so even if they have the same count they still have some order defined
				return strcasecmp($lastAText, $lastBText);
			} else {
				return $b['count'] - $a['count'];
			}
		});
		Debug::log('created sorted list of incidences by message');
		Debug::log($messageCounts);

		// get the information of the most popular messages in form of a block of text
		Debug::log('creating formatted response');
		$formattedResponse = $this->getPopularMessagesResponse($messageCounts, $limit);

		// if we found at least one top command output all
		Debug::log('found ' . count($messageCounts) .' messages with ' . $this->minOccurrencesToShowMessage . ' incidences or more');
		if (count($messageCounts) > 0) {
			$this->reply($formattedResponse)->otherwise(function() use($messageCounts, $limit){
				// if we found that the top commands response is too long (make the request fail)
				// we try again but enforcing and truncating the responses
				$safeFormattedResponse = $this->getPopularMessagesResponse($messageCounts, $limit, true);
				$this->postOnChannel(tt('command.top.text_long'));
				$this->postOnChannel($safeFormattedResponse);
			});
		} else {
			// otherwise reply
			$this->reply(tt('command.top.end_empty'));
		}
	}

	/**
	 * Recursive function to get the full message history of a channel
	 * @param Message $message
	 * @param callable $onDone
	 * @param callable $onFail
	 */
	private function retrieveFullMessageHistory(?string $lastMessageId, callable $onDone, callable $onFail)
	{
		// get the last 100 messages before the message passed in
		$historyParams = [
			'limit' => 100
		];
		if($lastMessageId){
			$historyParams['before'] = $lastMessageId;
		}
		$this->interaction->channel->getMessageHistory($historyParams)->then(function (Collection $messages) use($lastMessageId, $onDone, $onFail){
			Debug::log('retrieved ' . count($messages) . ' messages before: ' . $lastMessageId);
			// when we get the history of messages we will evaluate if we neeed to make another call to get more messages
			// or we reached the end of the channel history
			$rawMessages = array_values($messages->toArray());

			// sort by date asc
			usort($rawMessages, function($a, $b){
				return strtotime($a['timestamp']) - strtotime($b['timestamp']);
			});
			$this->allMessages = array_merge($rawMessages, $this->allMessages);

			// update the message on the channel to show the user the progress
			$this->reply(sprintf(tt('command.top.middle'), count($this->allMessages), $this->maxNumberOfMessagesToRetrieve));

			// we either reached the end of the channel or reached our max limit of messages we want to retrieve
			if(empty($rawMessages) || count($this->allMessages) >= $this->maxNumberOfMessagesToRetrieve){
				Debug::log('finished retrieving raw message history');
				$onDone();
			} else {
				Debug::log('retrieved ' . count($this->allMessages) . ' messages so far ');
				Debug::log('retrieving messages before:');
				Debug::log($this->allMessages[0]);
				$this->retrieveFullMessageHistory($this->allMessages[0]->id, $onDone, $onFail);
			}
		}, $onFail);
	}

	/**
	 * get the information of the most popular messages in form of a block of text
	 * @param array $messageCounts - objects in the form of
	 * {
	 *   "messages": [
	 *       Object of type Message
	 * 	 ],
	 *   "count": 2
	 * }
	 * @param $limit - the max amounts of results desired
	 * @param false $enforceSafeResponseLengthLength - determines if we should truncate the results
	 * @return string - the message formatted
	 */
	private function getPopularMessagesResponse(array $messageCounts, int $limit, bool $enforceSafeResponseLengthLength = false): string
	{
		// this value will be used in case we are retrying the message, after trying to send a message too long
		// will truncate all messages longer than 60
		$safeMessageContentLength = 100;

		// process each message in the count relation
		$lines = [];
		$lines[] = Text::bold(sprintf(tt('command.top.start'), count($this->allMessages)) . ':');
		// calculate if the actual results are less than the limit requested, then return only as much results as we can calculate
		// otherwise return the same number of elements the limit is requesting
		$min = min($limit, count($messageCounts));
		for($i = 0; $i < $min; $i++){
			$messageCount = $messageCounts[$i];
			// get the last message that repeated this text
			$last = $messageCount['last_message'];
			$messageContent = $last->content;
			// if the message it's too long and we are trying to enforce a safe length, truncate it
			if($enforceSafeResponseLengthLength && strlen($messageContent) > $safeMessageContentLength){
				$messageContent = substr($messageContent, 0, $safeMessageContentLength) . '...';
			}
			$lines[] = sprintf('> %s (%d %s, %s %s)',
				Text::code($messageContent),
				$messageCount['count'],
				tt('command.top.item_times'),
				tt('command.top.item_by'),
				Text::bold($last->author)
			);
		}

		// convert the array of lines into a single block of text
		return array_reduce($lines, function ($response, $line) use($enforceSafeResponseLengthLength){
			$updatedResponse = $response . $line . PHP_EOL;
			if($enforceSafeResponseLengthLength && strlen($updatedResponse) > 2000){
				return $response;
			} else {
				return $updatedResponse;
			}
		}, '');
	}
}