<?php

namespace util;

use GuzzleHttp\Client;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Factory;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;

class Gif
{

	/**
	 * @var Gif
	 */
	private static Gif $instance;

	// we will make a request to the giphy api
	private string $defaultGifUrl;

	/**
	 * url of the giphy API to get those GIF images
	 * @var string
	 */
	private string $giphyUrl;

	/**
	 * Storage constructor
	 */
	private function __construct()
	{
		$this->defaultGifUrl = 'https://media.tenor.com/images/172d63b92f17fb1d90eb37e64bbee10e/tenor.gif';
		$this->giphyUrl = "https://api.giphy.com/v1/gifs/random";
	}

	/**
	 * @return Gif
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			self::$instance = new Gif();
		}
		return self::$instance;
	}

	/**
	 * @param $key
	 * @return string
	 */
	public function random($key, $nsfw = false)
	{
		// prepare the api call, giphy allow us to set these arguments
		$params = [
			// the developer giphy key
			'api_key' => $_ENV['GIPHY_API_KEY'],
			// switch to avoid get NSFW images
			'rating' => 'g',
			// classification
			'tag' => $key
		];
		// if we allow nsfw gifs to show, then we need to remove the rating param
		if($nsfw){
			unset($params['rating']);
		}
		$query = http_build_query(
			$params
		);
		$client = new Client();
		$gifUrl = null;
		$promise = $client
			->getAsync($this->giphyUrl . '?' . $query)
			->then(function($response) use(&$gifUrl) {
				$body = json_decode($response->getBody(), true);
				// the response returned a random gif
				if(isset($body['data']['url'])){
					$gifUrl = $body['data']['url'];
				}
			});

		// this is like await from JS
		$promise->wait();

		// return the gif url or fallback to a static url
		return $gifUrl ?? $this->defaultGifUrl;
	}
}