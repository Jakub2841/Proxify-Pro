# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- PP-1: Initial layout, navbar, dependencies
- PP-2: Dashboard, Tracked, Passing Now, average latency, active sources, search filters, table, passed state
- PP-3: Proxy model, migration, seeder, Eloquent queries, live filtering, pagination, country names
- PP-4: Sources CRUD + Proxy anonymity filter
- PP-5: Proxy export system (CSV/TXT/JSON), shared filter scope, rate limiting
- PP-6: REST API (JSON + TXT), rate limiting, API documentation page
- PP-7: Settings page + code review fixes (validation, deduplication, debounced search)
- PP-8: Source format detection, HTML table column detection, parser engine, JSON/plain text scrapers, queue pipeline, bulk upsert, source auto-detection UI
- PP-9: Proxy checker (google + cloudflare), parallel Http::pool with connect_timeout, country geo-lookup, per-proxy queue jobs, auto-scheduling with live countdown timers, settings page refactor, API access gate, source import/export, database clear, proxy dedup/deletion
- PP-10: Docker deployment (PHP-FPM + Nginx + Supervisor), MySQL support, production mode with UI lockdown, env config overhaul, README, 146 tests

### Changed

### Deprecated

### Removed

### Fixed

- Geo-location lookups now route through the proxy being tested instead of connecting directly from the server, so the geo service sees the proxy's actual location

### Security
