# TaskFlow — Task Management API

A full-stack Task Management system built with **PHP**, **MySQL**, **HTML/CSS/Vanilla JS**.

---

## 📁 Project Structure

```
taskmanager/
├── api/
│   └── index.php          ← All API routes (REST)
├── config/
│   └── database.php       ← DB connection + .env loader
├── models/
│   └── Task.php           ← Task model / queries
├── public/
│   ├── index.html         ← Frontend UI
│   ├── style.css          ← Styles
│   └── app.js             ← Frontend JavaScript
├── database/
│   └── migrate.sql        ← Database migration + seed
├── .env.example           ← Environment template
├── .htaccess              ← Apache URL rewriting
└── README.md
```

---

## ⚙️ Running Locally (VS Code + XAMPP or Laragon)

### Step 1 — Install a local PHP/MySQL server

**Option A: XAMPP** (recommended for beginners)
- Download from https://www.apachefriends.org
- Install and open XAMPP Control Panel
- Start **Apache** and **MySQL**

**Option B: Laragon** (cleaner, modern)
- Download from https://laragon.org
- Start All services

---

### Step 2 — Place the project in the web root

**XAMPP:**
```
C:\xampp\htdocs\taskmanager\   (Windows)
/Applications/XAMPP/htdocs/taskmanager/   (Mac)
```

**Laragon:**
```
C:\laragon\www\taskmanager\
```

Copy or clone the entire project into that folder.

---

### Step 3 — Set up your `.env` file

```bash
# In the project root, copy the example file:
cp .env.example .env
```

Edit `.env` with your local MySQL credentials:
```
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=taskmanager
DB_USERNAME=root
DB_PASSWORD=         ← leave blank for XAMPP default
```

---

### Step 4 — Create the database

Open your browser and go to **http://localhost/phpmyadmin**

1. Click **"New"** in the left sidebar
2. Name it `taskmanager`, click **Create**
3. Click the **SQL** tab
4. Open `database/migrate.sql` in VS Code, copy all contents
5. Paste into phpMyAdmin SQL editor and click **Go**

This creates the `tasks` table and inserts 5 sample tasks.

---

### Step 5 — Open the app

Visit: **http://localhost/taskmanager/public/index.html**

The API is at: **http://localhost/taskmanager/api/tasks**

---

## 🔌 API Endpoints

### 1. Create Task
```
POST /api/tasks
Content-Type: application/json

{
  "title": "Fix login bug",
  "due_date": "2026-04-10",
  "priority": "high"
}
```

### 2. List Tasks
```
GET /api/tasks
GET /api/tasks?status=pending
GET /api/tasks?status=in_progress
GET /api/tasks?status=done
```

### 3. Update Task Status
```
PATCH /api/tasks/{id}/status
Content-Type: application/json

{ "status": "in_progress" }
```
Flow: `pending → in_progress → done` (cannot skip or revert)

### 4. Delete Task
```
DELETE /api/tasks/{id}
```
Only `done` tasks can be deleted. Returns `403` otherwise.

### 5. Daily Report (Bonus)
```
GET /api/tasks/report?date=2026-04-01
```
Returns task counts grouped by priority and status for that date.

---

## 🧪 Testing with REST Client (VS Code)

Install the **REST Client** extension in VS Code, then create `test.http`:

```http
### List all tasks
GET http://localhost/taskmanager/api/tasks

### Create a task
POST http://localhost/taskmanager/api/tasks
Content-Type: application/json

{
  "title": "Write unit tests",
  "due_date": "2026-04-15",
  "priority": "medium"
}

### Advance task status (replace 1 with actual id)
PATCH http://localhost/taskmanager/api/tasks/1/status
Content-Type: application/json

{ "status": "in_progress" }

### Delete task
DELETE http://localhost/taskmanager/api/tasks/1

### Daily report
GET http://localhost/taskmanager/api/tasks/report?date=2026-04-01
```

---

## 🚀 Deploying Online (Railway + PlanetScale)

### Option A — Railway (easiest, recommended)

**Step 1 — Push code to GitHub**
```bash
git init
git add .
git commit -m "initial commit"
# Create a repo on github.com and push
git remote add origin https://github.com/YOUR_USERNAME/taskmanager.git
git push -u origin main
```

**Step 2 — Create Railway project**
1. Go to https://railway.app and sign up
2. Click **"New Project" → "Deploy from GitHub repo"**
3. Select your `taskmanager` repo

**Step 3 — Add MySQL on Railway**
1. In your Railway project, click **"+ New" → "Database" → "MySQL"**
2. Railway will create a MySQL instance automatically

**Step 4 — Set environment variables**
In Railway project → your PHP service → **Variables**, add:
```
DB_HOST=     ← from Railway MySQL "Connect" tab
DB_PORT=     ← usually 3306
DB_DATABASE= ← from Railway MySQL
DB_USERNAME= ← from Railway MySQL
DB_PASSWORD= ← from Railway MySQL
```

**Step 5 — Run migrations**
In Railway MySQL service → **Query** tab, paste the contents of `database/migrate.sql` and run.

**Step 6 — Update `API_BASE` in app.js**
```js
// Change this line in public/app.js:
const API_BASE = 'https://YOUR-APP.railway.app/api';
```

**Step 7 — Access your app**
Railway gives you a URL like: `https://taskmanager-production.up.railway.app`

---

### Option B — Render + PlanetScale

**Step 1 — Set up PlanetScale (free MySQL)**
1. Go to https://planetscale.com, create an account
2. Create a new database → name it `taskmanager`
3. Click **"Connect"** → choose **PHP (PDO)** → copy the credentials

**Step 2 — Deploy on Render**
1. Go to https://render.com
2. New → **Web Service** → Connect your GitHub repo
3. Set **Runtime** to `PHP`
4. Set **Build Command** to (leave blank or `echo done`)
5. Set **Start Command** to `php -S 0.0.0.0:$PORT -t public`
6. Add environment variables (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD) from PlanetScale

> Note: On Render's free tier, the service sleeps after inactivity.

---

## 📌 Business Rules Summary

| Rule | Behaviour |
|------|-----------|
| Duplicate title + due_date | Returns `409 Conflict` |
| due_date in the past | Returns `422 Unprocessable` |
| Priority not in low/medium/high | Returns `422` |
| Status skip (pending → done) | Returns `422` |
| Status revert | Returns `422` |
| Delete non-done task | Returns `403 Forbidden` |
| Delete non-existent task | Returns `404 Not Found` |

---

## 📄 License
MIT — free to use for evaluation purposes.
