# Proxify Pro

> **Live version:** [proxify.madebyjakub.dev](https://proxify.madebyjakub.dev)

A self-hosted proxy management dashboard. Add sources (plain text lists or JSON APIs), scrape thousands of proxies, check them against Google and Cloudflare in parallel, and export or access them via a REST API. Built with Laravel 13, Livewire 4, Flux UI, and Tailwind CSS v4.

---

## Quick start (Docker)

```bash
git clone https://github.com/star/proxify-pro.git
cd proxify-pro
docker compose up --build
```

Open `http://localhost:8080`. Migrations run automatically. 16 queue workers start in the background. Redis and MySQL are included.

### Requirements

- Docker & Docker Compose
- No PHP, Composer, Node, or database needed on your host

---

## Features

### Proxy management
- **Scrape sources** — plain text (`ip:port` per line) and JSON API responses with recursive structure detection
- **Check proxies** — dual-target validation against google.com and cloudflare.com in parallel via `Http::pool()`
- **Auto-scheduling** — configurable scrape and check intervals with live countdown timers
- **Three-layer dedup** — in-batch PHP, cross-batch PHP, database `UNIQUE(address, port)` index
- **Country geo-lookup** — ip-api.com lookup per proxy during checking
- **Bulk upsert** — `whereIn` + chunked inserts for high-throughput ingestion

### Sources
- **Add/edit/delete** proxy sources with format auto-detection
- **Detect format** — automatically identifies plain text, JSON API, or HTML table sources
- **Import/export** — JSON export/import with URL-based deduplication
- **GitHub URL rewrite** — auto-converts `github.com/.../blob/...` → `raw.githubusercontent.com`

### Dashboard
- **Live filters** — protocol, anonymity level, check type, country, active-only, search
- **Sorting** — by latency or last checked date
- **Real-time stats** — tracked, passing now, average latency, active sources
- **Countdown timers** — Alpine.js live timers for next scrape and next check

### Settings
- Scrape interval with enable/disable toggle
- Check interval with enable/disable toggle
- Max latency threshold (proxy timeout)
- API access gate (blocks routes + hides sidebar link)
- Remove not passing proxies toggle
- Clear database with confirmation modal

### Export
- **Web** — CSV, TXT, JSON with filter support, rate limited at 10 req/min
- **API** — paginated JSON (`/api/proxies`) and plain text (`/api/proxies/txt`)

### REST API

| Endpoint | Rate Limit | Description |
|---|---|---|
| `GET /api/proxies` | 60/min | Paginated JSON with filters |
| `GET /api/proxies/txt` | 60/min | Plain text `protocol://ip:port` list |

Filter params: `protocols[]`, `anonymity[]`, `checks[]`, `country`, `active_only`, `per_page`

API access can be toggled from the settings page.

---

## Tech stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Frontend | Livewire 4 + Flux UI + Tailwind CSS v4 |
| Queue | Redis + 16 parallel workers |
| Database | MySQL (production) / SQLite (dev & tests) |
| Testing | Pest 4 — 146 tests |
| Container | Docker (PHP 8.3 FPM + Nginx + Supervisor) |

---

## Development (local)

```bash
# Install dependencies
composer install
npm install && npm run dev

# Copy env and generate key — see .env.example for all options
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate:fresh

# Start the dev server
composer run dev

# In another terminal, start the queue worker
php artisan queue:work
```

### Running tests

```bash
php artisan test
```

146 tests covering parsers, scrapers, checkers, Livewire components, commands, and middleware.

---

## Architecture

```
┌─────────────┐    ┌──────────┐    ┌──────────┐
│   Browser    │    │  Redis   │    │  MySQL   │
│  (Livewire)  │    │  Queue   │    │   DB     │
└──────┬───────┘    └────┬─────┘    └────┬─────┘
       │                 │               │
       ▼                 ▼               ▼
┌──────────────────────────────────────────────────┐
│                    PHP App                        │
│  ┌──────────┐ ┌──────────┐ ┌──────────────────┐ │
│  │ Scrape   │ │  Check   │ │ Auto Scheduler   │ │
│  │ Sources  │ │ Proxies  │ │ (every minute)   │ │
│  └──────────┘ └──────────┘ └──────────────────┘ │
│  ┌──────────────────────────────────────────────┐ │
│  │        16 Queue Workers (Supervisor)         │ │
│  └──────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────┘
```

---

## License

MIT
