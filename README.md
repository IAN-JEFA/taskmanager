# Task Ops Console

A full-stack Task Management system built for the Laravel Engineer Intern take-home
assignment: Laravel 11 + MySQL API, React (Vite) frontend, with login/register and a
"mission control" style dashboard.

```
task-management-system/
├── backend/     Laravel API (drop these files into a fresh Laravel install)
└── frontend/    React (Vite) single-page app
```

This zip does **not** contain a full `vendor/` or `node_modules/` (those are generated
by composer/npm). Follow the steps below in order — they assume **VS Code** as the
editor and **XAMPP** for MySQL + PHP.

---

## 0. Prerequisites

Install these once, if you don't have them already:

| Tool | Why | Check with |
|---|---|---|
| XAMPP | Gives you MySQL + phpMyAdmin (and PHP, but see note below) | — |
| PHP 8.2+ | Laravel 11 requires it | `php -v` |
| Composer | Installs Laravel + packages | `composer -V` |
| Node.js 18+ / npm | Runs the React frontend | `node -v` |
| VS Code | Editor | — |

> **Note on PHP:** XAMPP ships its own PHP under `C:\xampp\php` (Windows) or
> `/Applications/XAMPP/xamppfiles/bin` (Mac). Either add that folder to your PATH, or
> install PHP separately and just use XAMPP for MySQL — both work. The instructions
> below use whichever `php` and `mysql` your terminal resolves to.

Recommended VS Code extensions: **PHP Intelephense**, **Laravel Extension Pack**,
**ES7+ React/Redux snippets**, **Tailwind CSS IntelliSense** (not required, just handy).

---

## 1. Start MySQL in XAMPP

1. Open the **XAMPP Control Panel** → click **Start** next to **MySQL** (and Apache, if
   you also want phpMyAdmin's UI — not required for the API itself).
2. Open `http://localhost/phpmyadmin`.
3. Click **New** → create a database named exactly `task_management` → **Create**.
   (Migrations will build the tables inside it — you don't need to create any tables
   by hand.)

---

## 2. Set up the Laravel backend

Open a terminal in the folder where you want the project, then:

```bash
# 1. Create a fresh Laravel 11 app
composer create-project laravel/laravel task-ops-backend
cd task-ops-backend

# 2. Add Sanctum (token-based API auth)
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Now copy the files from this zip's `backend/` folder into your new `task-ops-backend/`
project, **overwriting** the matching paths:

```
backend/app/Models/Task.php                              → app/Models/Task.php
backend/app/Models/User.php                               → app/Models/User.php
backend/app/Http/Controllers/Api/AuthController.php       → app/Http/Controllers/Api/AuthController.php
backend/app/Http/Controllers/Api/TaskController.php       → app/Http/Controllers/Api/TaskController.php
backend/app/Http/Requests/StoreTaskRequest.php            → app/Http/Requests/StoreTaskRequest.php
backend/app/Http/Requests/UpdateTaskStatusRequest.php     → app/Http/Requests/UpdateTaskStatusRequest.php
backend/database/migrations/2026_01_01_000001_create_tasks_table.php → database/migrations/
backend/database/factories/TaskFactory.php                → database/factories/TaskFactory.php
backend/database/seeders/DatabaseSeeder.php               → database/seeders/DatabaseSeeder.php (overwrite)
backend/routes/api.php                                    → routes/api.php (create this file)
backend/config/cors.php                                   → config/cors.php (overwrite)
backend/bootstrap/app.php                                 → bootstrap/app.php (overwrite — see note)
backend/.env.example                                      → merge the DB_* and SANCTUM_* values into your .env
```

In VS Code: open the `task-ops-backend` folder (`code .`), then just drag-and-drop /
copy-paste each file above from the zip into the matching path. The `Api` folder under
`Http/Controllers` doesn't exist yet in a fresh Laravel install — create it first.

> **About `bootstrap/app.php`:** Laravel 11's default `bootstrap/app.php` doesn't wire
> up `routes/api.php` or the Sanctum middleware out of the box. The version in this zip
> adds both. If you've already customized your `bootstrap/app.php`, just apply the two
> highlighted changes (the `api:` line in `withRouting()` and the `EnsureFrontendRequestsAreStateful`
> middleware) instead of overwriting the whole file.

### Configure `.env`

Open `.env` (created automatically by `composer create-project`) and set:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=
```

(Default XAMPP MySQL has no root password. If you set one, put it in `DB_PASSWORD`.)

### Run migrations + seed demo data

```bash
php artisan migrate --seed
```

This creates the `users` and `tasks` tables and a demo account:
`demo@taskops.dev` / `password123`, plus 14 sample tasks (some due today, for testing
the report endpoint).

### Start the API

```bash
php artisan serve
```

The API is now live at `http://localhost:8000`, with all endpoints under
`http://localhost:8000/api/...`.

---

## 3. Set up the React frontend

In a **second terminal**:

```bash
# From the folder containing this zip's extracted "frontend" folder:
cd frontend
npm install
cp .env.example .env
npm run dev
```

Vite will print a local URL, normally `http://localhost:5173`. Open it in your browser.

The `.env` file's `VITE_API_BASE_URL` already points to
`http://localhost:8000/api`, matching `php artisan serve`'s default port — no changes
needed unless you run the backend on a different port.

---

## 4. How the frontend and backend are wired together

1. **CORS**: `backend/config/cors.php` explicitly allows `http://localhost:5173` (Vite's
   default port) to call the API. If you run the frontend on a different port, add it
   to the `allowed_origins` array and restart `php artisan serve`.
2. **Auth**: Login/register hit `POST /api/login` and `POST /api/register`, which
   return a Sanctum **Bearer token**. The React `AuthContext`
   (`frontend/src/context/AuthContext.jsx`) stores that token in `localStorage` and the
   Axios client (`frontend/src/api/client.js`) automatically attaches it as
   `Authorization: Bearer <token>` on every request.
3. **Task requests**: All task endpoints require that header (`auth:sanctum`
   middleware in `routes/api.php`), so once you're logged in, every fetch, create,
   status update, and delete call in the dashboard is already authenticated.
4. **404 → login**: If a token expires or is invalid, the API returns `401`, and the
   Axios response interceptor automatically clears local storage and redirects to
   `/login`.

To point the frontend at a different backend (e.g. once deployed), just change
`VITE_API_BASE_URL` in `frontend/.env` and rebuild/restart.

---

## 5. Example API requests

```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Ada Lovelace","email":"ada@example.com","password":"password123","password_confirmation":"password123"}'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@taskops.dev","password":"password123"}'
# → copy the "token" field from the response for the requests below

# Create a task
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer <TOKEN>" -H "Content-Type: application/json" \
  -d '{"title":"Write onboarding doc","due_date":"2026-08-01","priority":"high"}'

# List tasks (optionally filter by status)
curl "http://localhost:8000/api/tasks?status=pending" \
  -H "Authorization: Bearer <TOKEN>"

# Advance status (pending -> in_progress -> done, no skipping)
curl -X PATCH http://localhost:8000/api/tasks/1/status \
  -H "Authorization: Bearer <TOKEN>" -H "Content-Type: application/json" \
  -d '{"status":"in_progress"}'

# Delete (only allowed once status = done, otherwise 403)
curl -X DELETE http://localhost:8000/api/tasks/1 \
  -H "Authorization: Bearer <TOKEN>"

# Daily report
curl "http://localhost:8000/api/tasks/report?date=2026-07-03" \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 6. Business rules implemented

| Rule | Where |
|---|---|
| Title can't duplicate another task on the same due date | Unique DB index (`unique_title_per_due_date_per_user`) + `StoreTaskRequest` validation, scoped per user |
| `priority` must be low / medium / high | `Rule::in()` in `StoreTaskRequest` + DB enum |
| `due_date` must be today or later | `after_or_equal:today` in `StoreTaskRequest` |
| List sorted by priority (high→low) then due_date asc | `orderByRaw("FIELD(priority,'high','medium','low')")` in `TaskController::index` |
| Optional `status` filter on list | Query param in `TaskController::index` |
| Meaningful JSON when no tasks exist | `{"message": "No tasks found.", "data": []}` |
| Status only progresses pending → in_progress → done | `Task::STATUS_FLOW` + `Task::canTransitionTo()` |
| Delete only allowed when status = done, else 403 | `TaskController::destroy` |
| Daily report: counts per priority × status for a date | `TaskController::report`, matches the PDF's exact JSON shape |

> **Note:** tasks are scoped per logged-in user (`user_id` foreign key) since the
> assignment was extended to require login/register. Every list/report/update/delete
> only ever touches the current user's own tasks.

---

## 7. Deploying online (per the assignment's requirement)

**Backend (Railway or Render):**
1. Push the `task-ops-backend` folder to a GitHub repo.
2. On Railway/Render, create a new **Web Service** from that repo, add a **MySQL**
   database add-on, and copy its connection details into the service's environment
   variables (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_PORT`).
3. Set `APP_KEY` (`php artisan key:generate --show` locally, paste the value), and
   `APP_URL` to your deployed URL.
4. Add a release/start command that runs `php artisan migrate --force --seed` once,
   then `php artisan serve --host 0.0.0.0 --port $PORT` (or use the platform's PHP
   buildpack, which usually handles this automatically).
5. Add your deployed frontend's URL to `config/cors.php`'s `allowed_origins`.

**Frontend (Vercel, Netlify, or Render Static Site):**
1. Push the `frontend` folder to a GitHub repo (or a subfolder of the same repo).
2. Import it into Vercel/Netlify, set the build command `npm run build`, output
   directory `dist`.
3. Add an environment variable `VITE_API_BASE_URL` pointing at your deployed backend's
   `/api` URL.

---

## 8. Project structure reference

```
backend/
  app/Http/Controllers/Api/AuthController.php   register, login, logout, me
  app/Http/Controllers/Api/TaskController.php   index, store, updateStatus, destroy, report
  app/Http/Requests/                            validation rules per endpoint
  app/Models/Task.php                           status-transition logic
  database/migrations/…create_tasks_table.php   schema + unique/index constraints
  database/factories/TaskFactory.php            fake data for seeding
  database/seeders/DatabaseSeeder.php           demo user + demo tasks
  routes/api.php                                all API routes
  config/cors.php                               allows the Vite dev server origin
  bootstrap/app.php                             registers routes/api.php + Sanctum middleware

frontend/
  src/api/client.js               Axios instance + auth header interceptor
  src/context/AuthContext.jsx     login/register/logout state, localStorage persistence
  src/components/ProtectedRoute.jsx
  src/components/TaskCard.jsx     priority beacon, status pill, advance/delete actions
  src/components/TaskFormModal.jsx
  src/components/ReportPanel.jsx  date-scoped daily report table
  src/pages/Login.jsx / Register.jsx / Dashboard.jsx
  src/styles/index.css            dark glassmorphism "ops console" theme
```

---

## 9. Troubleshooting

- **CORS error in the browser console** → check `frontend/.env`'s port matches an
  entry in `backend/config/cors.php`'s `allowed_origins`, then restart
  `php artisan serve`.
- **`SQLSTATE[HY000] [1049] Unknown database`** → you skipped creating
  `task_management` in phpMyAdmin (step 1).
- **401 on every request right after logging in** → check the browser's Network tab:
  the `Authorization: Bearer …` header should be present. If it's missing, clear
  `localStorage` and log in again.
- **`Class "Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful" not found`**
  → run `composer require laravel/sanctum` before copying in `bootstrap/app.php`.
