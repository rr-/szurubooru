dc-up: 
	docker compose up -d

dc-down:
	docker compose down --volumes

dc-restart:
	make dc-down
	make dc-up

dc-build:
	docker compose build

dc-rebuild:
	make dc-build
	make dc-restart