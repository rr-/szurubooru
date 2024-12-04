dc-up: 
	docker compose up -d

dc-down:
	docker compose down --volumes

dc-reset:
	make dc-down
	make dc-up

