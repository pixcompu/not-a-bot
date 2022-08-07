# Phony target will execute 'make'
.PHONY: init

# Essential Make CMDs

# This procedure should be enough to get your container running.
init:
	@make down
	@make up
	@make ps
	@make migrate

# Will stop the container and remove everything, but the image.
# Read more: https://docs.docker.com/compose/reference/down/
down:
	docker-compose down --volumes --remove-orphans
pull:
	docker-compose pull

# Will build a container from the docker compose file
# Read more: https://docs.docker.com/compose/reference/build/
build:
	docker-compose build

# Will build a container from the docker compose file
# Read more: https://docs.docker.com/compose/reference/build/
up:
	docker-compose up -d
reload:
	docker-compose stop
	docker-compose up -d
full_prune:
	@make down
	docker system prune --all --volumes --force
bash:
	docker exec -it not-a-bot /bin/bash
logs:
	docker-compose logs -f --tail 100