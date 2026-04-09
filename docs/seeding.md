Seeding (Factories & Seeders)

Overview
- Centralized config lives in `config/seeding.php`.
- Environment-aware seeders: `BaseSeeder`, `DevSeeder`, `DemoSeeder`, `TestSeeder`.
- Idempotency:
  - Production: only `BaseSeeder` (safe, update-or-create)
  - Dev/Demo/Test: truncates non-reference tables inside a transaction, then reseeds.
- Determinism in tests: honors `Faker::seed()` and `mt_srand()` via `config('seeding.seed')` when `APP_ENV=testing`.

Usage
- Development (default):
  - `php artisan migrate:fresh --seed`
  - Seeds `BaseSeeder` + `DevSeeder` with volumes from `config/seeding.php`.
- Demo with custom volume:
  - `SEED_QTD=200 php artisan db:seed --class=DemoSeeder`
  - Use env var `SEED_QTD` to scale volumes across entities (no custom CLI option required). Defaults are in `config/seeding.php`.
  - Alternatively set `SEED_DEMO=true` and run `php artisan migrate:fresh --seed` to always run the `DemoSeeder`.
- Testing (deterministic, minimal):
  - `APP_ENV=testing php artisan migrate:fresh --seed`
  - Runs `BaseSeeder` + `TestSeeder` with fixed seed and small dataset.

Configuration
- `config/seeding.php` controls:
  - `faker_locale`: defaults to `pt_BR` for realistic data.
  - `seed`: RNG seed for tests.
  - `weights`: weighted distributions for statuses/enums.
  - `volumes`: default counts and per-lead interaction ranges.

Factories
- All factories now:
  - Use the app Faker (respects locale/seed) — no direct `Faker::create()`.
  - Reset unique scopes per `definition()` to avoid collisions.
  - Apply weighted distributions from `config('seeding.weights.*')`.
  - Ensure referential integrity via `afterCreating()` for child relations.

Adding Enums/States
- Add new values to the relevant migration/enum.
- Update `config/seeding.php` weights to include the new keys with realistic proportions.
- Adjust factories to map weights → enum cases when needed.

Notes
- Seeders use transactions for larger batches and avoid N+1 in hot paths.
- Dev/Demo seeders truncate only in non-production environments.
- Final seeding prints a small summary with row counts per table.
