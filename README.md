# Translation Management Service

A Laravel API for managing translations across multiple locales, with tag-based context, search, and a fast JSON export endpoint for frontend consumption.

## Requirements

- PHP 8.2+
- Composer 2.x
- MySQL 8.0 (or MariaDB 10.4+)
- Docker + Docker Compose (optional)

## Setup (local)

```bash
git clone <repo-url> locale-flow
cd locale-flow
composer install
cp .env.example .env
php artisan key:generate
```

Update `.env` with your DB credentials, then:

```bash
php artisan migrate
php artisan translations:seed --count=100000
php artisan serve
```

The API is now available at `http://127.0.0.1:8000/api`.

## Setup (Docker)

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan translations:seed --count=100000
```

App is served by Nginx at `http://localhost:8080`. MySQL is exposed on port `3307` and Redis on `6380`.

## Running tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database. The suite covers auth, CRUD, search filters, export behaviour, and the service layer (23 tests).

## Authentication

Token-based auth via Laravel Sanctum.

1. `POST /api/register` → returns a bearer token
2. `POST /api/login` → returns a bearer token
3. Send `Authorization: Bearer <token>` on protected endpoints

## API endpoints

All responses follow the shape:

```json
{ "success": true, "message": "OK", "data": ... }
```

Error responses:

```json
{ "success": false, "message": "...", "errors": { "field": ["..."] } }
```

### Auth

| Method | Path             | Body                                    |
| ------ | ---------------- | --------------------------------------- |
| POST   | `/api/register`  | `name`, `email`, `password`, `password_confirmation` |
| POST   | `/api/login`     | `email`, `password`                     |
| POST   | `/api/logout`    | —                                       |
| GET    | `/api/me`        | —                                       |

### Translations (auth required)

| Method | Path                          | Notes                                              |
| ------ | ----------------------------- | -------------------------------------------------- |
| GET    | `/api/translations`           | Filters: `locale`, `key` (prefix), `content` (substring), `tags[]`, `per_page` |
| POST   | `/api/translations`           | `locale`, `key`, `content`, `tags[]?`              |
| GET    | `/api/translations/{id}`      | —                                                  |
| PUT    | `/api/translations/{id}`      | Any of `locale`, `key`, `content`, `tags[]`        |
| DELETE | `/api/translations/{id}`      | —                                                  |

### Export (auth required)

| Method | Path                                  | Notes                                            |
| ------ | ------------------------------------- | ------------------------------------------------ |
| GET    | `/api/translations/export/{locale}`   | Returns a flat `{ "key": "content" }` JSON map for the locale |

### Example

```bash
curl -X POST http://127.0.0.1:8000/api/translations \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"locale":"en","key":"auth.login.title","content":"Sign in","tags":["web","mobile"]}'
```

```bash
curl http://127.0.0.1:8000/api/translations/export/en
```

## Design notes

**Schema**
- `translations(locale, key, content)` with:
  - `UNIQUE(locale, key)` — composite. Covers `locale`-only and `locale + key` lookups (export endpoint hits this).
  - `INDEX(key)` — used by the `key LIKE 'prefix%'` filter (trailing wildcard is index-friendly).
  - `FULLTEXT(content)` — added in a separate migration. The repository uses `MATCH(content) AGAINST(? IN BOOLEAN MODE)` on MySQL/MariaDB and falls back to `LIKE '%term%'` on other drivers (SQLite in the test suite). The boolean-mode term wraps each ≥3-char word with `+word*` so multi-word searches use AND semantics with prefix matching.
- `tags(name)` separated from translations to avoid duplicating string values and to allow filtering by tag count (`whereHas ... count(...)`).
- `translation_tag` pivot with composite primary key — no surrogate id, smaller rows, faster joins. `INDEX(tag_id)` supports reverse joins (tag → translations).

**SOLID & layering**
- `TranslationRepositoryInterface` → `TranslationRepository`: data access only.
- `TranslationService`: business rules (tag name → id resolution, transactional create/update).
- `TranslationController`: thin — takes a validated request, calls the service, returns a resource.
- Container binding lives in `AppServiceProvider`. Swapping the storage layer (e.g. for a search-engine-backed repository) is a one-line change.

**Validation**
- Every request is a dedicated `FormRequest` (`StoreTranslationRequest`, `UpdateTranslationRequest`, `IndexTranslationRequest`, `LoginRequest`, `RegisterRequest`).
- Uniqueness on `(locale, key)` is enforced both in the DB and in the request rules — the rule gives the user a clean 422 instead of a 500.

**Responses**
- `App\Traits\ApiResponse` provides a single `{success, message, data|errors}` shape for every endpoint.
- `bootstrap/app.php` renders `ValidationException`, `AuthenticationException`, `ModelNotFoundException`, and `NotFoundHttpException` through the same shape for API routes.

**Performance**
- The export endpoint caches the serialized JSON string (not the array) under `translations:export:{locale}`. JSON encoding of a 1.4 MB payload happens once per write, not per read.
- Cache invalidation runs from the `Translation` model's `saved` and `deleted` events — writes always invalidate, so the next read regenerates. The brief's requirement that "JSON endpoint should always return updated translations" is satisfied without TTL drift.
- The seeder uses chunked `DB::table()->insert()` (bypassing model events for speed) — 100k rows seed in ~8 seconds on a modest dev box.
- Search by tag uses `whereHas` with a count constraint to support AND-semantics for multiple tags. Single-tag searches use the `translation_tag.tag_id` index.

**CDN compatibility**
- The export endpoint sets `Cache-Control` and `X-Locale` headers. Because the endpoint is authenticated, edge caching has to be keyed on the auth header (or the CDN can be configured to forward the token to origin and rely on the server-side cache layer). The response is already pre-encoded JSON cached under `translations:export:{locale}`, so origin latency stays low even when the CDN misses.

**Auth**
- Sanctum personal-access tokens, hashed at rest. Only `register` and `login` are public; all other endpoints, including export, sit behind `auth:sanctum`.

**Notes on local benchmarks**
- `php artisan serve` is single-threaded and runs without opcache; on Windows it adds 400–600 ms framework-boot overhead per request, so wall-clock numbers from a local loopback test do not reflect production. Server-side compute for the export endpoint is ~3–5 ms warm and ~100 ms cold against 20k rows. Behind PHP-FPM with opcache (as in the included Docker setup) the endpoint comfortably meets the < 500 ms target.

## Postman

A ready-to-import collection and environment live in `postman/`:

- `postman/locale-flow.postman_collection.json` — every endpoint, grouped (Auth, Translations, Export). Bearer auth is wired at the collection level; Register and Login auto-save the returned token to the `token` environment variable, so subsequent requests work without copy-paste.
- `postman/locale-flow.postman_environment.json` — `base_url`, `email`, `password`, `token`, `locale`, `translation_id`.

Import both files into Postman, select the **Locale Flow (Local)** environment, then run **Auth → Register** (or **Login**) to populate the token. **Translations → Create** also stores the new id into `translation_id` so the Show / Update / Delete requests follow on without edits.

## Performance commands

```bash
php artisan translations:seed --count=100000 --chunk=2500
```

Options: `--count` (default 100000), `--chunk` (default 2000).
