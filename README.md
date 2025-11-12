## Pimono

A Laravel 12 API (`api/`) paired with a Vue 3 + Vite client (`app/`). The backend talks to PostgreSQL and Redis, while the frontend consumes the API over HTTP and receives broadcasts through Pusher-compatible websockets.

### Repo Layout

- `api/` – Laravel application, queues, websockets broadcasting, Docker stack.
- `app/` – Vue 3 SPA built with Vite and Tailwind.

---

## Local Setup (No Docker)

### Prerequisites

| Tool | Version / Notes |
| --- | --- |
| PHP | 8.4 with `pgsql`, `redis`, `bcmath`, `pcntl`, `gd` extensions |
| Composer | 2.7+ |
| Node.js / npm | Node 20.x (matches `.nvmrc`); npm 10+ |
| PostgreSQL | 16+; create a database named `pimono` and a user with full rights |
| Redis | 7+ |

> Tip: Run PostgreSQL and Redis locally (brew, apt, etc.) or point the `.env` files at managed services.

### 1. Backend (`api/`)

1. `cd api`
2. Copy and edit the environment file:
   ```bash
   cp .env.example .env
   ```
   Update at least:
   - `APP_URL=http://127.0.0.1:8000`
   - `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=pimono`, etc.
   - `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379`
3. Install dependencies and bootstrap the app:
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   npm install
   ```
4. Start the backend. Two options:
   - Full stack (PHP server, queue worker, Vite dev server, log tail):
     ```bash
     composer run dev
     ```
   - Manual processes:
     ```bash
     php artisan serve --host=127.0.0.1 --port=8000
     php artisan queue:listen
     npm run dev   # for asset hot reload
     ```

### 2. Frontend (`app/`)

1. `cd ../app`
2. Prepare environment variables:
   ```bash
   cp .env.example .env
   ```
   Set `VITE_API_URL="http://127.0.0.1:8000/api/"` (or whichever host/port you used for the backend) and the Pusher keys you expect.
3. Install and run:
   ```bash
   npm install
   npm run dev
   ```
4. Visit the client at `http://localhost:5173` (Vite default) to interact with the locally running API.

---

## Docker-Based Setup

Use Docker when you want a reproducible Linux stack for the Laravel API, PostgreSQL, and Redis. The Vue client still runs locally via Node (so Vite hot-module reload remains fast), but it targets the Dockerized API.

### Backend containers

1. `cd api`
2. Copy `.env.example` to `.env` and leave the default service hostnames (`DB_HOST=db`, `REDIS_HOST=redis`). Set `APP_URL=http://localhost:8080`.
3. Build and start the stack:
   ```bash
   docker compose up --build -d
   ```
   Services: `pimono-app` (nginx + php-fpm + queue worker via Supervisor), `pimono-db` (PostgreSQL), `pimono-redis`.
4. Because the project directory is bind-mounted into the container, run installs/migrations inside the container once:
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --seed
   docker compose exec app npm install
   docker compose exec app npm run build   # or npm run dev if you want to serve assets from Docker
   ```
5. Access the API at `http://localhost:8080`. Container logs:
   ```bash
   docker compose logs -f app
   ```

### Frontend when the API runs in Docker

1. Run the client locally (same steps as in “Frontend” above).
2. Point `VITE_API_URL` to `http://localhost:8080/api/` so the SPA talks to the containerized backend.
3. Start Vite: `npm run dev`, then browse to `http://localhost:5173`.

---

## Helpful Commands

- Tests (backend): `cd api && php artisan test`
- Linting/format (backend): `cd api && ./vendor/bin/pint`
- Build frontend for production: `cd app && npm run build`
- Stop Docker stack: `cd api && docker compose down`
