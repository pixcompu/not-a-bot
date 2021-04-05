# Not a bot
Project intended to research discord bot capabilities and custom usage

Psst... it's not a bot it's a cyborg

![resultadoCiborgDesk](https://user-images.githubusercontent.com/11744752/113503542-2cd6b180-94f8-11eb-8bcd-c5a85e241224.png)

## Pre-requisites
- **+18.09** docker
- **+1.23** docker-compose

## Installation
1. open the CMD on the root folder of the project
2. execute `docker-compose up -d`
3. execute `docker exec not-a-bot composer install`
4. execute `docker exec not-a-bot php ./entrypoint.php`

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

![Screen Shot 2021-04-05 at 1 43 05](https://user-images.githubusercontent.com/11744752/113545714-4d624280-95b0-11eb-9aff-2a8618ec3413.png)