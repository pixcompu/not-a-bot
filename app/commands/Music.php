<?php


namespace app\commands;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Voice\VoiceClient;

class Music extends Command
{

	/**
	 * play a static music file if the user that triggered the command is in a voice channel
	 * @throws \Exception
	 */
	public function execute(): void
	{
		// get the guild where the bot was called from the db
		$guild = $this->message->channel->guild;

		// the guild can have
		$this->messageDiscord->guilds->fresh($guild)->then(function(Guild $guild){
			// attempt to get the voice client that is playing currently in the voice channel
			// a voice client should exists if the bot is currently connected to a voice channel
			$voiceClient = $this->messageDiscord->getVoiceClient($this->message->channel->guild_id);

			// as a test we will just finish the current voice client if we got requested the music command while the music is playing
			if(isset($voiceClient)){
				$voiceClient->close();
			} else {
				// to find the voice channel the user is connected
				// we need to find all the voice states from the guils (voice states are combinations of voice channels and users)
				$firstVoiceChannel = $guild->channels->get('type', Channel::TYPE_VOICE);

				// if we found successfully the channel then we attempt to join the voice channel
				if (isset($firstVoiceChannel)) {
					$this->messageDiscord->joinVoiceChannel($firstVoiceChannel)->then(function(VoiceClient $voiceClient){
						$this->message->reply(tt('command.music.player_start'));
						$this->play($voiceClient);
					});
					// the user that triggered the command isn't on a voice channel
				} else {
					$this->message->reply(tt('command.music.invalid_channel'));
				}
			}
		});
	}

	/**
	 * Play a static file on the voice channel
	 * @param VoiceClient $voiceClient
	 */
	private function play(VoiceClient $voiceClient)
	{
		// play raw sound file
		$voiceClient->playFile(__DIR__.'/../../test.mp3')
			->then(function() use ($voiceClient){
				// this is executed when the music stopped playing
				$voiceClient->close()->then(function(){
					// successfully played the file and abandoned the voice channel
					$this->message->reply(tt('command.music.player_end'));
				}, function(){
					// error while abandoning the voice channel
					$this->message->reply(tt('command.music.player_end_error'));
				});
			}, function(){
				// error while playing the file
				$this->message->reply(tt('command.music.player_start_error'));
			});
	}
}