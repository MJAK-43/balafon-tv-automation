# Release checklist: Windows and vMix 25/29

Use this checklist for every release candidate. Record exact versions and do
not mark a version compatible until all required real-vMix checks pass.

## Release identity

- Git branch: `release/windows-vmix-25-29`
- Candidate tag: `v0.1.0-rc.1`
- Windows release artifact:
- SHA-256:
- Tester:
- Test date:

## Source quality gate

- [ ] `composer install --no-interaction` succeeds
- [ ] `npm ci` succeeds
- [ ] `php artisan test` succeeds
- [ ] `npm run build` succeeds
- [ ] migrations and seed succeed on a new SQLite database
- [ ] `VMIX_DRIVER=mock` smoke test succeeds
- [ ] no `.env`, SQLite database, log, media, `vendor`, `node_modules`, `dist`,
      portable PHP runtime, or temporary browser profile is committed

## Clean Windows installation

- [ ] install succeeds on a machine without Composer or Node.js
- [ ] bundled PHP is detected, or the missing prerequisite is explained clearly
- [ ] a new SQLite database is created
- [ ] `C:\ProgramData\Balafon\Media` is writable
- [ ] Balafon starts on `http://127.0.0.1:8080`
- [ ] the scheduler starts and stops with Balafon
- [ ] reinstalling does not leak or overwrite unrelated user data
- [ ] paths containing spaces and accented characters work

## Real vMix matrix

Run the same media and playlist fixture on each version.

| Check | vMix 25 result | vMix 29 result |
| --- | --- | --- |
| Exact version and edition detected from XML | NOT RUN | NOT RUN |
| Connection to port 8088 | NOT RUN | NOT RUN |
| `AddInput` / input GUID resolution | NOT RUN | NOT RUN |
| `Play`, `Pause`, `Restart`, `SetPosition` | NOT RUN | NOT RUN |
| Preview/Program switch | NOT RUN | NOT RUN |
| `Running` to `Completed` monitoring | NOT RUN | NOT RUN |
| Playlist progression and cleanup | NOT RUN | NOT RUN |
| Logo image overlay | NOT RUN | NOT RUN |
| Browser ticker overlay | NOT RUN | NOT RUN |
| Announcement video overlay and loop | NOT RUN | NOT RUN |
| Connection loss and recovery | NOT RUN | NOT RUN |
| Paths with spaces and accents | NOT RUN | NOT RUN |

## vMix configuration

- Enable the vMix Web Controller.
- Use port `8088`.
- For a same-PC test, configure `VMIX_HOST=127.0.0.1`.
- For a remote-PC test, use the vMix PC address, allow TCP 8088 through the
  firewall, and use media paths that the vMix PC can access.
- Keep the vMix preset, media fixture, Balafon database, and test order identical
  between vMix 25 and vMix 29.

## Acceptance rule

A release candidate can be merged and tagged only when:

1. all automated tests and the production frontend build pass;
2. the generated package installs on a clean Windows environment;
3. each advertised vMix version passes every required real-vMix scenario;
4. known limitations are included in the release notes.
