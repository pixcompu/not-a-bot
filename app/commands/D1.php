<?php

namespace app\commands;

use util\Debug;
use util\Gif;
use util\Text;

class D1 extends Command
{

	/**
	 * invokes all the channel to play a game
	 */
	public function execute(): void
	{
		// invoke all the channel to play with a mention
		$this->reply(
			sprintf(tt('command.d1.main'), Text::mention('here'), Text::bold($this->interaction->user->username), Text::bold($this->getGameName()))
		)->then(function() {
			// show a gif related to the game
			$giphyKey = $this->getGiphyKey();
			Debug::log($giphyKey);
			$this->postMessage(
				Gif::getInstance()->random($giphyKey, true)
			);
		});
	}

	/**
	 * Returns the key that we will use to find a gif to post in the channel
	 * @return string
	 */
	private function getGiphyKey()
	{
		// we want to make the gif sometimes being related to the game and sometimes chosen for a selected list of keywords randomly
		// we will take 100 as our max, so here we define the probability of displaying a gif related to the game (from 100 max)
		$defaultGameKeyPercentage = 50;

		// define the custom gif keys, each one of these have the same probability of displaying
		$secretGameKeys = [
			'nagatoro',
			'spy x family',
			'lets go',
			'waifu'
		];

		// if we have at least one secret game keys
		if(count($secretGameKeys) > 0){
			// set the percentage as the remaining from the max - the percentage already taken
			$secretGameKeyPercentage = 100 - $defaultGameKeyPercentage;
			// for the percentage remaining divide it by each of the secret keywords
			$secretGameKeyPercentagePerItem = round($secretGameKeyPercentage / count($secretGameKeys));
			// generate a random number between 1 and the max percentage (100)
			$randomPercentageNumber = rand(1, $defaultGameKeyPercentage + $secretGameKeyPercentage);
			// if the number is greater than the default percentage means that falls into any of the secret keys (>50)
			Debug::log('random number: ' . $randomPercentageNumber);
			if($randomPercentageNumber > $defaultGameKeyPercentage){
				// for each key we calculate it's lower and upper bound and check if the number falls in the range
				foreach($secretGameKeys as $i => $secretGameKey) {
					/*
						example of the logic:
						default = 50
						secret (keys A and B) = 50

						keys
							A = 25
							B = 25

						random number = 83

						range from A
							min: 50 + 0 * 25 = 50
							max: min + 25 = 75

						range from B
							min: 50 + 1 * 25 = 75
							max: min + 25 = 100

						75 <= 83 < 100 = key returned B
					*/
					$minSecretGameKeyIndex = $defaultGameKeyPercentage + ($i * $secretGameKeyPercentagePerItem);
					$maxSecretGameKeyIndex = $minSecretGameKeyIndex + $secretGameKeyPercentagePerItem;
					if ($minSecretGameKeyIndex < $randomPercentageNumber && $randomPercentageNumber < $maxSecretGameKeyIndex) {
						return $secretGameKey;
					}
				}
			}
		}

		// by default return the game name as the key to search the gif
		return $this->getGameName();
	}

	/**
	 * Get the formatted name of the game passed in
	 * @return string
	 */
	private function getGameName()
	{
		$gameNameMap = [
			's' => 'Super Smash Bros',
			'f' => 'Fortnite'
		];
		$gameKey = $this->options['game']['value'];
		// usually we just write the abbreviation of the game instead of the full name, so here we translate the abbreviations to the full name
		return $gameNameMap[strtolower($gameKey)] ?? $gameKey;
	}


}