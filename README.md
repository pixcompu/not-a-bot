# Not a bot
Project intended to research discord bot capabilities and custom usage

Psst... it's not a bot it's a cyborg

![resultadoCiborgDesk](https://user-images.githubusercontent.com/11744752/113503542-2cd6b180-94f8-11eb-8bcd-c5a85e241224.png)

## Pre-requisites
- **+18.09** docker
- **+1.23** docker-compose
- Bot application created on [discord developer portal](https://discord.com/developers/docs/intro)
- Firebase [real time database](https://firebase.google.com/products/realtime-database) project created in [google console](https://console.firebase.google.com/)

## Installation
1. copy `.env.example` to generate a `.env` file
3. [generate a private json key file](https://firebase.google.com/docs/admin/setup#initialize-sdk) to authenticate with firebase and place it on the `/secrets` folder
2. configure your environment variables
3. open the CMD on the root folder of the project
4. execute `docker-compose up -d`
5. execute `docker exec not-a-bot composer install`
5. execute `docker exec -it not-a-bot /bin/bash`
6. once in the root folder of the project inside the container, execute `php ./entrypoint.php`
7. invite the bot to your server, you need to generate the invite link as the following (replacing `<YOUT CLIENT ID GOES HERE>` with the value of your client id):

`https://discord.com/api/oauth2/authorize?client_id=<YOUT CLIENT ID GOES HERE>&scope=bot&permissions=4265606897`

Your can get your client id from the OAuth2 section of your application page in the discord developer portal.

## Deployment

### Direct PHP script execution

1. If you have it already running find the process with `ps -fa`
2. Kill the process with `kill <pid>`
3. Execute the php script in the background writing the output to a log file with
`nohup php /home/fadm/projects/not-a-bot/entrypoint.php &`
4. If you want to check the log file execute `tail nohup.out -f`


## Usage

Note: Once the container is up, if you make changes to the code, you have to interrupt the `entrypoint.php` process
and fire it up again to see the changes reflected on the bot.

### Stop
1. execute `docker stop not-a-bot`

### Start
1. execute `docker-compose up -d`

### Refresh build
1. execute `docker-compose up -d --build`

## Create a new command

To create a new command you need to do the following:
1. Create a new class under the directory `app/commands` which extends the base Command class
```php
<?php

namespace app\commands;

class Ping extends Command
{
	/**
	 * reply to the user
	 */
	public function execute(): void
	{
		$this->reply('Pong!');
	}
}
```
_Note: for slash commands you need to answer the command in less than 3 seconds (with `reply` or `acknowledge`) or it will get invalidated_

2. Reference the new class created in the `app/Bot.php::getStaticCommandDefinitions()` method
```php
private function getStaticCommandDefinitions()
{
	return [
		...
		// rest of the commands definitions will be here
		'ping' => [
			'class' => \app\commands\Ping::class
		]
	];
}
```

3. Create a lang entry in `lang/es.json` for the new command (at least `name` and `description` keys are required)
```json
{
  ...
  "ping": {
	"name": "Ping",
	"description": "Es un commando de prueba",
	"done": "Soy un pong!"
  }
}
```

4. If you want to use any text in your command, it is preferred to put it in the lang file and invoke it with the `tt` function, so our new reply will look like this:
```php
$this->reply(tt('command.ping.done'));
```

5. Make sure that the setting `SET_UP_COMMANDS_ON_START` is set to `1` in the env file, so your new command gets created when the project is executed,
after the command is created you can set that setting to `0` to save time when reloading the project multiple times on development.

6. Restart your discord client (new slash commands can take some seconds to be effective on the server, is better to reload your discord client to refresh the list of slash commands).

7. Type `/ping` on your test server (where your test bot is invited) and it should answer `Soy un pong!`

Congratulations! you did your first slash command!

## Screenshots

### Command list
![image](https://user-images.githubusercontent.com/42556506/230278619-12bf9ff7-004f-4b11-bcad-4a3aa6882fd8.png)

### Meow
![image](https://user-images.githubusercontent.com/42556506/230278710-6280732e-6469-48bf-a87f-a0eec52cb4b3.png)

### Response
![image](https://user-images.githubusercontent.com/42556506/230278968-a5fa1e00-2e8a-4e6b-80ef-0da3c407f81f.png)

### Top
![image](https://user-images.githubusercontent.com/42556506/230279333-71b4a744-8013-4d7e-9f24-3c99ca494abe.png)

### Catalog
![image](https://user-images.githubusercontent.com/42556506/230279430-9f60e7b8-0e9d-4d1c-8eca-2b89a702a81f.png)

It also have some other interesting commands like **encode** (converts a piece of text into a GIF), **decode** (converts the GIF url back into the text message), **d1** (invokes all the people on the server to play a game)