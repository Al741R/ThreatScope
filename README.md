# ThreatScope

**ThreatScope** is a decoupled full-stack **Software Asset Watchlist & Live Vulnerability Intelligence Dashboard**. It allows IT administrators and developers to track their internal software stack (vendors, products, and versions) in a local database and run real-time CVE scans against the public **CIRCL Vulnerability-Lookup API**, complete with automated CVSS score extraction and color-coded severity triage.

---

## Key Features

- **Software Asset Watchlist (Full CRUD):** Create, view, update, retire, and delete tracked software assets stored locally in MySQL.
- **Automatic Input Normalization:** Trims whitespace and lowercases `vendor` and `product` inputs server-side via Laravel Form Requests to maintain format compatibility with external API lookups.
- **Live CIRCL API Proxy & Normalization:** Queries `https://vulnerability.circl.lu/api/search/{vendor}/{product}`, deduplicates overlapping CVEs across feeds (CVE 5.x, NVD 2.0, and legacy formats), extracts CVSS base scores, and returns a clean, uniform JSON response.
- **Ad-Hoc Quick Lookup:** Scan any `vendor` and `product` pair on the fly without adding it to the local database first.
- **Dynamic CVSS Severity Triage:** Automatically classifies and color-codes vulnerabilities (`Critical`, `High`, `Medium`, `Low`, `Unknown`), with interactive severity filter pills and client-side scan caching.
- **Clean Monorepo Architecture:** Houses both the Laravel REST API backend and the Vue 3 Single Page Application (SPA) frontend in a single repository.

---

## Tech Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | Laravel (PHP 8.2+), Eloquent ORM, Laravel HTTP Client |
| **Database** | MySQL (`threatscope_db`) |
| **Frontend** | Vue 3 (Composition API, plain JavaScript), Vite |
| **State & Routing** | Pinia, Vue Router, Axios |
| **Styling** | Vanilla CSS (no Tailwind CSS, no TypeScript) |
| **External Data Source** | [CIRCL Vulnerability-Lookup API](https://vulnerability.circl.lu/) |

---

## System Architecture

```text
+-----------------------------------+
|      Vue 3 Frontend (SPA)         |
|  http://localhost:5173            |
|  Pinia Stores | Vue Router | CSS  |
+-----------------+-----------------+
                  |  Axios (JSON over HTTP + CORS)
                  v
+-----------------------------------+
|     Laravel REST API Backend      |
|  http://127.0.0.1:8000/api        |
|  AssetController | ScannerCtrl    |
+--------+-----------------+--------+
         |                 |
         v                 v
+-----------------+  +------------------------------+
| MySQL Database  |  | CIRCL Vulnerability-Lookup   |
| threatscope_db  |  | https://vulnerability.circl.lu|
| (assets table)  |  | (Live CVE & CVSS Feeds)      |
+-----------------+  +------------------------------+
```

---

## Repository Structure

```text
ThreatScope/
├── README.md
├── threatscope-backend/                 # Laravel REST API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── AssetController.php      # CRUD endpoints for tracked assets
│   │   │   │   └── ScannerController.php    # CIRCL API proxy & JSON normalizer
│   │   │   └── Requests/
│   │   │       ├── StoreAssetRequest.php    # Create validation & normalization
│   │   │       └── UpdateAssetRequest.php   # Partial update validation
│   │   └── Models/
│   │       └── Asset.php                    # Eloquent model ($fillable)
│   ├── config/
│   │   └── cors.php                         # Cross-origin rules for Vue frontend
│   ├── database/
│   │   ├── migrations/
│   │   │   └── *_create_assets_table.php    # assets table schema
│   │   └── seeders/
│   │       ├── AssetSeeder.php              # 5 realistic starter software assets
│   │       └── DatabaseSeeder.php
│   ├── routes/
│   │   └── api.php                          # REST API route definitions
│   └── .env.example
│
└── threatscope-frontend/                # Vue 3 + Vite SPA
    ├── src/
    │   ├── assets/
    │   │   └── style.css                    # Global styles & CVSS severity classes
    │   ├── components/
    │   │   ├── AssetForm.vue                # Add / Edit software asset form
    │   │   ├── AssetList.vue                # Watchlist table with Scan/Edit/Delete
    │   │   └── VulnerabilityCard.vue        # Color-coded CVE & CVSS card
    │   ├── router/
    │   │   └── index.js                     # Routes: / (Dashboard), /assets (Watchlist)
    │   ├── services/
    │   │   └── api.js                       # Centralized Axios client
    │   ├── stores/
    │   │   ├── assetStore.js                # Pinia store for CRUD operations
    │   │   └── scannerStore.js              # Pinia store for CIRCL scan state & cache
    │   ├── views/
    │   │   ├── DashboardView.vue            # Live scanner dashboard & triage filters
    │   │   └── ManageAssetsView.vue         # Asset watchlist management view
    │   ├── App.vue                          # Root layout & navigation bar
    │   └── main.js                          # App entry point
    ├── index.html
    ├── package.json
    └── vite.config.js
```

---

## Prerequisites

Ensure you have the following installed on your machine:

- **PHP 8.2+** and **Composer**
- **MySQL** (recommended: [Laragon](https://laragon.org/) or XAMPP)
- **Node.js (v18+)** and **npm**
- **Git**

---

## Local Setup & Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Al741R/ThreatScope.git
cd ThreatScope
```

---

### 2. Backend Setup (`threatscope-backend`)

Open a terminal inside `threatscope-backend/`:

```bash
cd threatscope-backend
composer install
```

Create your local `.env` file:

```powershell
# Windows PowerShell
Copy-Item .env.example .env
```

```bash
# Git Bash / macOS / Linux
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Create a MySQL database named `threatscope_db` in **phpMyAdmin** or **HeidiSQL**, then configure the database connection in `threatscope-backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=threatscope_db
DB_USERNAME=root
DB_PASSWORD=
```

*(If your local MySQL `root` user has a password, place it inside quotes in `DB_PASSWORD="your_password"`.)*

Verify that `threatscope-backend/config/cors.php` allows your local Vue dev server:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173'],
'allowed_headers' => ['*'],
```

Clear cached config, run migrations with seed data, and start the API server:

```bash
php artisan config:clear
php artisan migrate --seed
php artisan serve
```

The Laravel API will be live at **`http://127.0.0.1:8000`**.

---

### 3. Frontend Setup (`threatscope-frontend`)

Open a **second terminal** inside `threatscope-frontend/`:

```bash
cd threatscope-frontend
npm install
```

*(Optional)* Create a `.env` file inside `threatscope-frontend/` if your Laravel API runs on a different host or port (defaults to `http://127.0.0.1:8000/api`):

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Start the Vite development server:

```bash
npm run dev
```

Open **`http://localhost:5173`** in your browser.

---

## Database Schema

### Table: `assets`

| Column | Type | Constraints / Default | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Primary Key, Auto-Increment | Unique asset identifier |
| `vendor` | `VARCHAR(255)` | Required, lowercased | Software vendor (e.g., `microsoft`, `apache`, `php`) |
| `product` | `VARCHAR(255)` | Required, lowercased | Software product (e.g., `windows_10`, `http_server`) |
| `version` | `VARCHAR(255)` | Required | Installed software version (e.g., `2.4.59`) |
| `status` | `ENUM` | `'Active'`, `'Retired'` (Default: `'Active'`) | Lifecycle status of the asset |
| `created_at` | `TIMESTAMP` | Nullable | Creation timestamp |
| `updated_at` | `TIMESTAMP` | Nullable | Last modification timestamp |

> **Tip for CIRCL Compatibility:** Use lowercase names and underscores for multi-word products (e.g., `windows_10`, `http_server`, `sql_server`) to match CPE/CIRCL naming conventions.

---

## REST API Reference

Base URL: `http://127.0.0.1:8000/api`  
Include the header `Accept: application/json` on all requests.

### Asset Watchlist (CRUD)

| Method | Endpoint | Description | Success Status |
| :--- | :--- | :--- | :--- |
| `GET` | `/assets` | Retrieve all tracked assets (latest first) | `200 OK` |
| `POST` | `/assets` | Add a new software asset | `201 Created` |
| `GET` | `/assets/{id}` | Retrieve a single asset by ID | `200 OK` |
| `PUT` / `PATCH` | `/assets/{id}` | Update an existing asset (supports partial updates) | `200 OK` |
| `DELETE` | `/assets/{id}` | Remove an asset from the watchlist | `200 OK` |

**Example `POST /api/assets` Body:**

```json
{
  "vendor": "apache",
  "product": "http_server",
  "version": "2.4.59",
  "status": "Active"
}
```

### Live Vulnerability Scanner (CIRCL API)

| Method | Endpoint | Description | Success Status |
| :--- | :--- | :--- | :--- |
| `GET` | `/assets/{id}/scan` | Scan a saved asset against the CIRCL API | `200 OK` |
| `GET` | `/scan?vendor={v}&product={p}` | Ad-hoc vulnerability lookup without saving | `200 OK` |

**Example Scanner Response (`200 OK`):**

```json
{
  "asset": {
    "id": 3,
    "vendor": "php",
    "product": "php",
    "version": "8.3.6",
    "status": "Active"
  },
  "query": {
    "vendor": "php",
    "product": "php",
    "endpoint": "https://vulnerability.circl.lu/api/search/php/php"
  },
  "summary": {
    "total": 25,
    "critical": 4,
    "high": 10,
    "medium": 9,
    "low": 2,
    "unknown": 0
  },
  "vulnerabilities": [
    {
      "cve_id": "CVE-2024-4577",
      "cvss_score": 9.8,
      "severity": "Critical",
      "summary": "Argument injection vulnerability in PHP CGI...",
      "published": "2024-06-06",
      "source": "cvelistv5",
      "url": "https://vulnerability.circl.lu/vuln/CVE-2024-4577"
    }
  ]
}
```

---

## CVSS Severity Classification

Vulnerabilities returned by Laravel are automatically sorted by highest CVSS score first and mapped to visual severity levels in Vue:

| Severity | CVSS Score Range | Visual Indicator |
| :--- | :--- | :--- |
| **Critical** | `9.0 – 10.0` | Deep red card with alert highlight |
| **High** | `7.0 – 8.9` | Deep orange accent |
| **Medium** | `4.0 – 6.9` | Amber / yellow accent |
| **Low** | `0.1 – 3.9` | Cool blue accent |
| **Unknown** | `null` (No score published) | Neutral slate |

---

## Team Collaboration & Git Workflow

To keep `main` stable and avoid merge conflicts:

1. **Never push directly to `main`.** Always create a dedicated feature or styling branch.
2. **Sync before starting a new task:**
   ```bash
   git checkout main
   git pull origin main
   git checkout -b feat/your-task-name
   ```
3. **Commit focused changes and push your branch:**
   ```bash
   git add .
   git commit -m "Describe your change clearly"
   git push -u origin feat/your-task-name
   ```
4. **Open a Pull Request (PR)** targeting `main` and request a review before merging.
5. **Frontend Contributors:** Feel free to enhance HTML structure, layout, responsiveness, and CSS, while preserving existing Vue bindings (`v-model`, `@click`, `@submit`, props, and Pinia store calls).
6. **Never commit ignored/sensitive files:** `.env`, `vendor/`, and `node_modules/` are intentionally excluded via `.gitignore`.

---

## Troubleshooting

### Laravel asks to create an SQLite database on `php artisan migrate`
Your `.env` file still defaults to SQLite or has cached config. Confirm `DB_CONNECTION=mysql` (with no `#` prefix) in `threatscope-backend/.env`, save the file, and run:
```bash
php artisan config:clear
php artisan migrate --seed
```

### `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`
- Verify your MySQL `root` password in `.env`.
- If using Laragon on Windows, ensure no standalone Windows MySQL service (`MySQL80`) is occupying port `3306`, or update `DB_PORT` to match Laragon's MySQL port.

### `502 Bad Gateway` / `cURL error 60: SSL certificate problem` during CIRCL scans
On Windows/Laragon, PHP's cURL may need a CA bundle path. Open your active `php.ini`, uncomment the following lines, point them to Laragon's certificate file, and restart `php artisan serve`:
```ini
curl.cainfo = "C:\laragon\etc\ssl\cacert.pem"
openssl.cafile = "C:\laragon\etc\ssl\cacert.pem"
```

### CORS Errors in the Browser Console
Confirm `threatscope-backend/config/cors.php` includes `'http://localhost:5173'` in `allowed_origins`, then run `php artisan config:clear` and restart Laravel.
