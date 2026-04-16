# Simple Dictionary WSS

Laravel-based WebSocket backend for Simple Dictionary. This service is responsible for real-time communication around training flows, matchmaking, and link-match rooms. It runs as a Docker-first application and relies on Redis and MongoDB during normal operation.

This repository is not a generic Laravel web app. The main runtime entrypoint is the WebSocket server started through `php artisan websocket:serve`.

## What This Service Does

- accepts WebSocket client connections
- authenticates clients and routes incoming messages
- handles training-related real-time events
- manages matchmaking flows
- manages link-match room lifecycle
- integrates with Redis-backed messaging and runtime coordination
- uses MongoDB-backed components where required by the current implementation

## Stack

- PHP 8.4
- Laravel
- Redis
- MongoDB
- Ratchet / ReactPHP WebSocket runtime
- PHPUnit
- Laravel Pint
- Docker Compose

## Quick Start

Create the local environment file:

```bash
cp .env.example .env
```

Start the development containers:

```bash
docker compose up -d
```

Open a shell in the application container:

```bash
docker compose exec wss_app bash
```

Install PHP dependencies if needed:

```bash
docker compose exec wss_app composer install
```

Start the WebSocket server:

```bash
docker compose exec wss_app php artisan websocket:serve
```

## Development Commands

Run the full test suite:

```bash
docker compose exec wss_app php artisan test
```

Run a filtered test set:

```bash
docker compose exec wss_app php artisan test --filter=LinkMatchRoom
```

Check formatting with Pint:

```bash
docker compose exec wss_app ./vendor/bin/pint --test
```

Format only dirty files:

```bash
docker compose exec wss_app ./vendor/bin/pint --dirty
```

## Docker Runtime

The main local workflow uses `docker-compose.yml`.

- `wss_app` is the main application container
- `wss_redis` provides Redis
- `wss_mongo` provides MongoDB

The production-style compose file is `docker-compose.prod.yml`. In that setup, the application container starts with:

```bash
php artisan websocket:serve
```

## Architecture

The codebase is split by responsibility rather than by framework defaults.

- `app/Application` contains use cases, orchestration, listeners, and application contracts
- `app/Domain` contains aggregates, domain events, enums, and core business rules
- `app/Infrastructure` contains adapters for Redis, API clients, locking, and other implementation details
- `app/WebSockets` contains the runtime, handlers, message dispatching, storage, listeners, and timers
- `tests/Unit` contains focused tests for domain and application behavior
- `tests/Feature` contains higher-level runtime and flow tests

## Environment Notes

- Docker is the primary way to run this service
- `.env.example` is configured around Docker container hostnames such as `wss_redis` and `wss_mongo`
- the root HTTP route exists, but it is not the product entrypoint for this repository
- built frontend assets exist in `public/build`, but frontend live-reload is not part of the main workflow for this service

If asset rebuilding is ever needed, keep it secondary to the backend workflow and run it explicitly inside the app container.

## Troubleshooting

If `docker compose exec wss_app ...` fails:

- make sure the containers are running with `docker compose up -d`
- confirm that the container name is `wss_app`

If the WebSocket server fails to boot:

- check Redis and Mongo connectivity from the container
- verify the `.env` values for Redis, MongoDB, and API-related settings
- confirm required PHP extensions such as MongoDB support are available in the container

If Artisan commands fail unexpectedly:

- check application logs and container logs
- verify file permissions for `storage` and `bootstrap/cache`
- make sure the environment file matches the active Docker setup

If tests fail inconsistently:

- verify dependent services are running
- remember that some tests interact with runtime services or spawn the WebSocket server

## Code Quality

This repository uses PHPUnit for tests and Laravel Pint for formatting.

There is no active `pest.php` configuration in the current repository state, so testing instructions should be based on PHPUnit / `php artisan test`.
