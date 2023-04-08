<?php

namespace app\commands;

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Interactions\Interaction;
use Discord\Voice\VoiceClient;
use util\Debug;

class Music extends Command
{
	/**
	 * @var ?VoiceClient
	 */
	private ?VoiceClient $voiceClient;

	public function __construct(Discord $discord, Interaction $interaction)
	{
		parent::__construct($discord, $interaction);
		// get the guild where the bot was called from the db
		$this->voiceClient = $this->discord->getVoiceClient($this->interaction->guild->id);
	}

	/**
	 * play a static music file if the user that triggered the command is in a voice channel
	 * @throws \Exception
	 */
	public function execute(): void
	{
		if(isset($this->voiceClient)){
			$this->voiceClient->close();
			$this->reply(tt('command.music.player_end'));
		} else {
			// to find the voice channel the user is connected
			// we need to find all the voice states from the guils (voice states are combinations of voice channels and users)
			$firstVoiceChannel = $this->interaction->guild->channels->get('type', Channel::TYPE_VOICE);

			// if we found successfully the channel then we attempt to join the voice channel
			if (isset($firstVoiceChannel)) {
				$this->reply(tt('command.music.player_start'));
				$this->discord->joinVoiceChannel($firstVoiceChannel, false, false)->then(function(VoiceClient $voiceClient){
					Debug::log('joined voice channel');
					Debug::log($voiceClient);
					$this->voiceClient = $voiceClient;
					$this->play();
				}, function($error){
					// error while playing the file
					$this->reply(tt('command.music.player_start_error'));
					Debug::log($error);
				});
				// the user that triggered the command isn't on a voice channel
			} else {
				$this->reply(tt('command.music.invalid_channel'));
			}
		}
	}

	/**
	 * Play a static file on the voice channel
	 */
	private function play()
	{
		Debug::log('attempting to play the file');
		// play raw sound file
		$this->voiceClient->playFile('https://stream.rcast.net/69485.mp3')
			->then(function() {
				// this is executed when the music stopped playing
				$this->voiceClient->close();
				// successfully played the file and abandoned the voice channel
				$this->reply(tt('command.music.player_end'));
			}, function(){
				// error while playing the file
				$this->reply(tt('command.music.player_start_error'));
			});
	}
}