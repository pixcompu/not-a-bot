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

![Screen Shot 2021-04-04 at 16 20 49](https://user-images.githubusercontent.com/11744752/113521856-c5ede280-9561-11eb-98a6-55a1e3481ccb.png)