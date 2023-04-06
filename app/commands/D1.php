<?php

namespace app\commands;

use util\Gif;
use util\Text;

class D1 extends Command
{

	/**
	 * invokes all the channel to play a game
	 */
	public function execute(): void
	{
		$gameKey = $this->options['game']['value'];

		// usually we just write the abbreviation of the game instead of the full name, so here we translate the abbreviations to the full name
		$gameNameMap = [
			's' => 'Super Smash Bros',
			'f' => 'Fortnite'
		];
		$gameName = $gameNameMap[strtolower($gameKey)] ?? $gameKey;

		// invoke all the channel to play with a mention
		$this->reply(
			sprintf(tt('command.d1.main'), Text::mention('here'), Text::bold($this->interaction->user->username), Text::bold($gameName))
		);

		// show a gif related to the game
		$gameGifKey = $gameName;
		$randomNumber = rand(0, 4);
		if($randomNumber === 0){
			// just add some variety to the gifs by introducing secret keys
			$gameGifKey = 'waifu';
		}
		$this->postOnChannel(
			Gif::getInstance()->random($gameGifKey, true)
		);
	}
}