SHELL := /bin/bash

.PHONY: dc-up
dc-up: ## Bring up the stack
	docker compose up -d

.PHONY: dc-up-dev
dc-up-dev: ## Bring up the stack
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

.PHONY: dc-down
dc-down: ## Bring down the stack
	docker compose down --volumes

.PHONY: dc-restart
dc-restart: ## Restart the stack
	make dc-down
	make dc-up

.PHONY: dc-build
dc-build: ## Build Docker images
	docker compose build

.PHONY: dc-rebuild
dc-rebuild: ## Rebuild Docker images and restart stack
	make dc-build
	make dc-restart

.PHONY: build-client
build-client: ## Builds the client locally
	cd ./client && npm run build

.PHONY: help
help:
	@awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"} /^[a-zA-Z_\-\\.\/0-9]+:.*?##/ { printf "  \033[36m%s:\033[0m%s\n", $$1, $$2 | "column -c2 -t -s :" }' $(MAKEFILE_LIST)