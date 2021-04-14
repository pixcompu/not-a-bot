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

## Usage

Note: Once the container is up, if you make changes to the code, you have to interrupt the `entrypoint.php` process
and fire it up again to see the changes reflected on the bot.

### Stop
1. execute `docker stop not-a-bot`

### Start
1. execute `docker-compose up -d`

### Refresh build
1. execute `docker-compose up -d --build`

### Example

![Screen Shot 2021-04-14 at 2 12 48](https://user-images.githubusercontent.com/11744752/114668983-00c0ea80-9cc7-11eb-9821-2c4d30457c8e.png)