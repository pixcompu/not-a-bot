<?php


namespace app\commands;


use GuzzleHttp\Client;

class Gif extends Command
{

	/**
	 * url of the giphy API to get those GIF images
	 * @var string
	 */
	private string $giphyUrl = "https://api.giphy.com/v1/gifs/search";

	/**
	 * Search a gif and take a random one
	 */
	public function execute(): void
	{
		// we will join all the args in case we received a full sentence as search string
		$keyword = implode(' ', $this->args);
		// prepare the api call, giphy allow us to set these arguments
		$query = http_build_query(
			[
				// the developer giphy key
				'api_key' => $_ENV['GIPHY_API_KEY'],
				// search string
				'q' => $keyword,
				// how many gifs and how many places we should ignore
				'limit' => 10,
				'offset' => 0,
				// switch to avoid get NSFW images
				'rating' => 'g'
			]
		);

		// we will make a request to the giphy api
		$client = new Client();
		$promise = $client
			->getAsync($this->giphyUrl . '?' . $query)
			->then(function($response){
				$body = json_decode($response->getBody(), true);
				// the response returned at lest one result
				if(count($body['data']) > 0){
					// the response contains up to 10 results, we will take a random one from those
					$index = rand(0, count($body['data']) - 1);
					$this->message->channel->sendMessage($body['data'][$index]['url']);
				} else {
					// the response did not return results
					$this->message->reply(tt('command.gif.empty'));
				}
			});
		$promise->wait();
	}
}