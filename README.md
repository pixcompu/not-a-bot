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

### Stop
1. execute `docker stop not-a-bot`

### Start
1. execute `docker-compose up -d`

### Refresh build
1. execute `docker-compose up -d --build`

### Example

You can trigger a anime image writting in some channel 'mona china'

![Screen Shot 2021-04-04 at 5 33 33](https://user-images.githubusercontent.com/11744752/113505927-5519dc80-9507-11eb-90b1-bbb83b9e74a4.png)