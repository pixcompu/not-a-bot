<?php


namespace app\commands;
use Discord\Voice\VoiceClient;

class Music extends Command
{

	public function execute(): void
	{
		$voiceStates = $this->message->channel->guild->voice_states;
		$userId = $this->message->author->id;

		$channel = null;
		foreach ($voiceStates as $voiceState) { //Find a voice channel the user is in
			if ($voiceState->user_id === $userId) {
				$channel = $this->discord->getChannel($voiceState->channel_id);
			}
		}

		if (isset($channel)) {
			$this->discord->joinVoiceChannel($channel)->then(function (VoiceClient $voice) {
				$voice->playFile(__DIR__.'./../../test.mp3')->otherwise(function($o){
					echo json_encode($o);
				})->then([$voice, 'close']);
			});
		} else {
			$this->message->reply(tt('command.music.invalid_channel'));
		}
	}
}