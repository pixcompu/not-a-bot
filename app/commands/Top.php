<?php

namespace app\commands;

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Message;
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
	private int $maxNumberOfMessagesToRetrieve = 3000;

	/**
	 * measures if the process is finished so the bot appears as typing
	 * @var bool
	 */
	private bool $commandIsInProgress = false;

	/**
	 * sanity check to the max times we want to call the broadcast typing function to avoid an infinite loop
	 * if something goes wrong
	 * @var int
	 */
	private int $broadcastTypingRetriesRemaining = 10;

	/**
	 * gives the most sent messages in a channel (based on the last 100 messages)
	 * @throws \Exception
	 */
	public function execute(): void
	{
		$limit = 5;
		// only allow up to 20 places
		if(isset($this->args[0])){
			if(is_numeric($this->args[0]) && in_array($this->args[0], range(1, 20))){
				$limit = intval($this->args[0]);
			} else {
				$this->message->reply(tt('command.top.wrong_limit'));
				return;
			}
		}

		// this potentially is a long operation, so we will make the bot appear like it's typing
		$this->commandIsInProgress = true;
		$this->notifyTyping();

		// get the guild where the bot was called from the db
		$this->retrieveFullMessageHistory($this->message, function()use ($limit){
			$this->showTopMessages($limit);
			$this->commandIsInProgress = false;
		}, function(){
			$this->message->reply(tt('command.top.error'));
			$this->commandIsInProgress = false;
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
		// first we will get a list of raw messages so we can use PHP built in functions to count
		$countByMessage = array_reduce($this->allMessages, function ($map, $message) {
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
			$lastAText = $a['messages'][count($a['messages']) - 1];
			$lastBText = $b['messages'][count($b['messages']) - 1];
			if($b['count'] === $a['count']){
				// to avoid randomize the results when they have the same count
				// we use as second criteria the order by message content
				// so even if they have the same count they still have some order defined
				return strcasecmp($lastAText, $lastBText);
			} else {
				return $b['count'] - $a['count'];	
			}
		});

		// get the information of the most popular messages in form of a block of text
		$formattedResponse = $this->getPopularMessagesResponse($messageCounts, $limit);

		// if we found at least one top command output all
		if (count($messageCounts) > 1) {
			$this->message->channel->sendMessage($formattedResponse)->otherwise(function() use($messageCounts, $limit){
				// if we found that the top commands response is too long (make the request fail)
				// we try again but enforcing and truncating the responses
				$safeFormattedResponse = $this->getPopularMessagesResponse($messageCounts, $limit, true);
				$this->message->channel->sendMessage(tt('command.top.text_long'));
				$this->message->channel->sendMessage($safeFormattedResponse);
			});
		} else {
			// otherwise reply
			$this->message->reply(tt('command.top.end_empty'));
		}
	}

	/**
	 * Recursive function to get the full message history of a channel
	 * @param Message $message
	 * @param callable $onDone
	 * @param callable $onFail
	 */
	private function retrieveFullMessageHistory(Message $message, callable $onDone, callable $onFail)
	{
		// get the last 100 messages before the message passed in
		$this->message->channel->getMessageHistory([
			'before' => $message,
			'limit' => 100
		])->then(function (Collection $messages) use($onDone, $onFail){
			// when we get the history of messages we will evaluate if we neeed to make another call to get more messages
			// or we reached the end of the channel history
			$rawMessages = array_values($messages->toArray());

			// sort by date asc
			usort($rawMessages, function($a, $b){
				return strtotime($a['timestamp']) - strtotime($b['timestamp']);
			});

			$this->allMessages = array_merge($rawMessages, $this->allMessages);
			// we either reached the end of the channel or reached our max limit of messages we want to retrieve
			if(count($rawMessages) < 100 || count($this->allMessages) >= $this->maxNumberOfMessagesToRetrieve){
				call_user_func($onDone);
			} else {
				$this->retrieveFullMessageHistory($this->allMessages[0], $onDone, $onFail);
			}
		}, $onFail);
	}

	/**
	 * notifies to the channel that the bot is typing
	 */
	private function notifyTyping()
	{
		$loop = $this->botDiscord->getLoop();
		$notificationActiveSeconds = 5;
		// set the initial notification, so the bot appears as typing
		$this->message->channel->broadcastTyping();
		// set a timer that will run each 5 seconds to check if the bot finished processing the message history
		// if not then it will notify the bot as still typing
		// if yes then it ends the timer so it doesn't run indefinetely
		$loop->addPeriodicTimer($notificationActiveSeconds, function(\React\EventLoop\Timer\Timer $timer) use(&$loop){
			// the broadcast tries are a safe check to ensure we end the timer
			if($this->commandIsInProgress && $this->broadcastTypingRetriesRemaining > 0){
				$this->broadcastTypingRetriesRemaining--;
				$this->message->channel->broadcastTyping();
			} else {
				// we finished processing cancel this timer to free memory
				$loop->cancelTimer($timer);
			}
		});
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
			$last = $messageCount['messages'][count($messageCount['messages']) - 1];
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
				Text::bold($last->author->username)
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