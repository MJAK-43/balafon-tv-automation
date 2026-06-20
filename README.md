# Balafon Broadcast Manager

Foundation for Phase 0 of Balafon Broadcast Manager.

## Scope

Current implementation covers:

- Docker development environment
- Laravel 12 API foundation
- Vue 3 SPA shell
- Sanctum token auth
- Audit logs
- System diagnostics
- vMix integration with real/mock provider abstraction
- Dashboard for diagnostics and vMix checks

## Development setup

1. Copy `.env.example` to `.env`.
2. Install PHP dependencies with `composer install`.
3. Install JS dependencies with `npm install`.
4. Align `.env` with `.env.example`.
5. Start Docker services with `docker compose up -d --build`.
6. Run migrations with `php artisan migrate`.
7. Seed baseline data with `php artisan db:seed`.
8. Start Vite with `npm run dev`.

## Default access

- Email: `admin@balafon.local`
- Password: `password`

## vMix modes

- Real mode: `VMIX_DRIVER=real`
- Demo/mock mode: `VMIX_DRIVER=mock`

When running inside Docker against a Windows host with vMix, use:

- `VMIX_HOST=host.docker.internal`
- `VMIX_PORT=8088`

## Phase 0 endpoints

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`
- `GET /api/v1/dashboard/summary`
- `GET /api/v1/system/health`
- `POST /api/v1/system/run-diagnostic`
- `GET /api/v1/vmix/status`
- `GET /api/v1/vmix/inputs`
- `POST /api/v1/vmix/connections/{uuid}/test`
- `PUT /api/v1/vmix/connections/{uuid}`
- `POST /api/v1/vmix/play-test`

## Notes

- Local machine PHP is currently `8.2.x`; Docker target is `8.4`.
- `Pest` is not fully wired yet because the local PHP runtime blocks the current compatible branch. PHPUnit remains available now, and Pest should be finalized inside the PHP 8.4 container track.
