# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 13 (PHP 8.4) + React 19 + Inertia.js 3 + TypeScript + Tailwind CSS 4 + Vite 8. Auth is handled by Laravel Fortify. UI primitives come from Radix UI (shadcn/ui style) in `resources/js/components/ui/`. The UI language is Bosnian/Serbian.

Docker services (via Laravel Sail): PostgreSQL 18 + Redis. The app container is named `SkyCore`.

## Commands

### Development
```bash
composer dev         # starts PHP server, queue worker, pail log viewer, and Vite concurrently
```

### Building
```bash
npm run build        # production frontend build
npm run build:ssr    # SSR build
```

### Testing
```bash
php artisan test                          # run all tests
php artisan test tests/Feature/Auth      # run a specific directory
./vendor/bin/pest --filter "test name"   # run a single test by name
```

### Linting & Formatting
```bash
composer lint            # PHP: pint --parallel (auto-fix)
composer lint:check      # PHP: pint --parallel --test (check only)
npm run lint             # JS/TS: eslint --fix
npm run lint:check       # JS/TS: eslint (check only)
npm run format           # Prettier write
npm run format:check     # Prettier check
npm run types:check      # tsc --noEmit
```

### Full CI check
```bash
composer ci:check   # lint:check + format:check + types:check + php artisan test
```

## Architecture

### Inertia + Layout Resolution

`resources/js/app.tsx` determines the layout from the Inertia page name:
- `welcome` → no layout
- `auth/*` → `AuthLayout`
- `settings/*` → `[AppLayout, SettingsLayout]` (nested)
- everything else → `AppLayout`

Individual pages can override this by setting a static `.layout` property (e.g. `KupacLogin.layout` uses `AuthSkyairLayout`).

### Wayfinder — Auto-generated Route Bindings

`resources/js/routes/` and `resources/js/actions/` are **generated** by the Wayfinder Vite plugin from PHP routes. Do not edit these files manually — they regenerate on every `vite` run. Import route helpers from these files to get type-safe URLs and form actions:

```ts
import { store } from '@/routes/login'   // .url(), .form(), etc.
```

### Domain Models

Core booking domain (`app/Models/`):
- `Flight` — a scheduled flight
- `FlightTicket` — a seat on a flight, linked to a `Reservation`
- `Reservation` — groups tickets for a user; has a `latestState` (via `ReservationState`), `Payment`, and optional `Cancellation`
- `Passenger` — profile attached to a `User` (stores credit card number)
- `Baggage`, `TicketClass`, `Payment`, `Cancellation`, `ReservationState` — supporting entities

### Path Alias

`@/` resolves to `resources/js/`. All imports within the frontend should use this alias.

### Queue

Jobs in `app/Jobs/` run via a Redis-backed queue. In `composer dev` the queue worker runs automatically. In Docker, the dedicated `queue` service handles this.
