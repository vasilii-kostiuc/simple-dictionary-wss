# AGENTS.md

## Purpose

This repository is a Laravel-based WebSocket service for Simple Dictionary. It contains application/domain logic for matchmaking, link-match rooms, training flows, client connection lifecycle, and message dispatching over WebSockets.

This file is for humans and coding agents working in the repo. Prefer the local repository state over generic Laravel assumptions.

## Current Stack

- PHP `^8.4`
- Laravel framework declared in `composer.json`: `^12.0`
- PHPUnit `^11.5`
- Laravel Pint `^1.13`
- Vite `^6`
- Tailwind CSS `^4`
- Redis via `predis/predis`
- MongoDB via `ext-mongodb` and `mongodb/mongodb`
- Ratchet / ReactPHP for WebSocket runtime
- Docker Compose for local infra

## Key Project Facts

- Tests are PHPUnit-based. There is no active `pest.php` config in the repo.
- Code style uses Laravel Pint.
- The main runtime is Docker-based.
- `docker-compose.yml` is for local development infra.
- `docker-compose.prod.yml` starts the app container with `php artisan websocket:serve`.
- The repo may often be dirty during development. Do not overwrite unrelated user changes.
- There is a custom lock configuration in `config/locks.php`.
- `.env.example` points at Redis and Mongo containers:
  - Redis host: `wss_redis`
  - Mongo host: `wss_mongo`

## Repository Structure

Top-level areas:

- `app/Application` - use cases, orchestration, application contracts, listeners
- `app/Domain` - aggregates, value objects, enums, domain events, domain contracts
- `app/Infrastructure` - Redis, API clients, shared infra adapters, identity adapters
- `app/WebSockets` - runtime, handlers, broker, listeners, messages, subscriptions, timers, storage
- `app/Console/Commands` - custom Artisan commands
- `config` - application and infra configuration, including `messaging.php` and `locks.php`
- `routes` - Laravel routes and console routes
- `tests/Feature` - flow/integration-style tests
- `tests/Unit` - focused unit tests for domain/application/websocket components

Important examples:

- `app/Application/LinkMatchRoom` - use cases around link-match room lifecycle
- `app/Domain/LinkMatchRoom` - aggregate and domain events for link-match rooms
- `app/Infrastructure/LinkMatchRoom/RedisLinkMatchRoomRepository.php` - Redis persistence for room state
- `app/WebSockets/Handlers/Client` - inbound client message handling
- `app/WebSockets/TrainingWsRuntime.php` - WebSocket runtime entry point

## Setup Commands

Create local env if needed:

```bash
cp .env.example .env
```

Start local development containers:

```bash
docker compose up -d
```

Install backend dependencies inside the app container when needed:

```bash
docker compose exec wss_app composer install
```

Install frontend dependencies inside the app container when needed:

```bash
docker compose exec wss_app npm install
```

Production-style compose file:

```bash
docker compose -f docker-compose.prod.yml up -d
```

## Run Commands

Primary workflow is via Docker containers.

Open a shell in the app container:

```bash
docker compose exec wss_app bash
```

Run tests in Docker:

```bash
docker compose exec wss_app php artisan test
```

Run a filtered test set in Docker:

```bash
docker compose exec wss_app php artisan test --filter=LinkMatchRoom
```

Run the custom WebSocket server in Docker:

```bash
docker compose exec wss_app php artisan websocket:serve
```

Frontend dev server in Docker:

```bash
docker compose exec wss_app npm run dev
```

Frontend production build in Docker:

```bash
docker compose exec wss_app npm run build
```

Local non-Docker fallback commands exist, but prefer Docker unless there is a specific reason not to.

## Code Style And Quality

Check all PHP files for Pint violations in Docker:

```bash
docker compose exec wss_app ./vendor/bin/pint --test
```

Check only dirty files in Docker:

```bash
docker compose exec wss_app ./vendor/bin/pint --test --dirty
```

Auto-format only dirty files in Docker:

```bash
docker compose exec wss_app ./vendor/bin/pint --dirty
```

Run a specific test file in Docker:

```bash
docker compose exec wss_app php artisan test tests/Unit/LinkMatchRoomTest.php
```

Filter tests by name in Docker:

```bash
docker compose exec wss_app php artisan test --filter=LinkMatchRoom
```

## Working Rules

- Prefer `rg` and `rg --files` for search.
- Use `apply_patch` for manual file edits.
- Do not revert unrelated changes in a dirty worktree.
- Do not amend commits unless explicitly requested.
- Do not assume Pest is configured just because the package may appear in lockfiles or plugin config.
- Keep domain rules in the domain layer. Keep infra concerns such as Redis, Mongo, locks, and framework integrations out of domain objects.

## Self-Check Before Finishing Work

For code changes, run the smallest useful verification first, then expand only if needed.

Minimum self-check:

1. Inspect the working tree.
2. Format changed PHP files with Pint.
3. Run targeted tests for the touched area.
4. If the change is broad or cross-cutting, run the wider test suite you can support.

Suggested commands:

```bash
git status --short
docker compose exec wss_app ./vendor/bin/pint --dirty
docker compose exec wss_app php artisan test --filter=LinkMatchRoom
docker compose exec wss_app php artisan test
```

If the repo is already dirty:

- verify only the files you changed;
- do not “clean up” unrelated files;
- mention pre-existing dirty files in your final handoff when relevant.

## Notes For Agents

- Prefer repository reality over framework defaults. The `README.md` is still the generic Laravel one.
- The most trustworthy sources here are `composer.json`, `package.json`, `config/*.php`, `app/**`, `tests/**`, and Docker files.
- When answering questions about architecture, inspect the current implementation first because this repo uses custom Application/Domain/Infrastructure separation plus a custom WebSocket runtime.
- Prefer `docker compose exec wss_app ...` for runtime, tests, and formatting commands.
