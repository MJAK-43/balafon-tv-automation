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

## Windows local mode

For tester-friendly Windows execution without Docker:

1. Run `scripts/install_windows_local.ps1`
2. Run `scripts/start_windows_local.ps1`

For the simplest local double-click flow on this machine:

- run `start_balafon_local.cmd`
- stop with `stop_balafon_local.cmd`

See [docs/windows-local-distribution.md](docs/windows-local-distribution.md) for details.

For a tester package that does not require Composer or Node.js on the destination machine:

1. Run `scripts/prepare_windows_release.ps1` on the build machine
2. Send `dist\windows-release\Balafon`
3. On the tester machine run `scripts/install_windows_tester.ps1`
4. Then run `scripts/start_windows_local.ps1`

Windows `.cmd` launchers are also included for non-technical testers:

- `scripts\install_windows_tester.cmd`
- `scripts\start_windows_local.cmd`
- `scripts\stop_windows_local.cmd`

If you also place a portable PHP runtime at `runtime\php\php.exe` before running `scripts\prepare_windows_release.ps1`, the generated tester package can run without a separate PHP installation on the destination machine.

## Large uploads

The Docker stack is configured to accept uploads above 5 GB:

- `upload_max_filesize=6144M`
- `post_max_size=6144M`
- `client_max_body_size 6G`

If you run the app through local WAMP/Apache/PHP instead of Docker, apply the same limits in your WAMP `php.ini` and web server config, then restart the services.
