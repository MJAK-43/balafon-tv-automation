# Release validation report: 2026-07-29

Windows distribution artifacts were rebuilt on 2026-07-30 after improving the
launchers and generating the Inno Setup installer. Application and vMix test
results below are unchanged.

## Candidate

- Branch: `release/windows-vmix-25-29`
- Candidate: `v0.1.0-rc.1`
- Archive: `Balafon-0.1.0-rc.1-windows-x64.zip`
- Archive size: 60.01 MB
- SHA-256: `346787A205959EFC1E8B1AF3E8549908DFC2E54DD1CCABACEF6D741CA2696E31`
- Installer: `Balafon-Setup.exe`
- Installer size: 43.07 MB
- Installer SHA-256: `95218D5D3785186CF86941F53804F3A9685FCD27EC4F9A0621EC3E6821C72530`

## Automated validation

| Check | Result |
| --- | --- |
| Composer install | PASS |
| npm clean install | PASS |
| npm security audit | PASS — 0 known vulnerabilities |
| Laravel test suite | PASS — 27 tests, 202 assertions |
| PHP syntax | PASS — 170 files |
| Production frontend build | PASS |
| Fresh SQLite migrations and seed | PASS |
| PowerShell syntax | PASS |
| High-confidence secret scan | PASS |

The Vite build reports one non-blocking warning: the main JavaScript chunk is
larger than 500 kB after minification.

## Windows package validation

| Check | Result |
| --- | --- |
| Bundled PHP 8.2.18 starts | PASS |
| Required PHP extensions | PASS |
| Project `.env` excluded | PASS |
| Build-machine SQLite databases excluded | PASS |
| Logs, runtime state, and uploaded media excluded | PASS |
| `vendor` and compiled frontend assets included | PASS |
| Installation in a path with spaces | PASS |
| Media path with accented characters | PASS |
| Application key generation | PASS |
| SQLite database creation, migrations, and seed | PASS |
| HTTP startup | PASS — status 200 |
| Scheduler startup and shutdown | PASS |
| Server shutdown without residual PHP process | PASS |
| ZIP content inspection | PASS — 10,347 entries |
| Inno Setup compilation | PASS — Inno Setup 6.7.3 |
| Installer metadata | PASS — version 0.1.0-rc.1 |
| Installer execution on a second PC | NOT RUN |

## vMix validation

### vMix 29

- Installed version detected: `29.0.0.48`
- Test launch attempted.
- The process started but exposed neither a main window nor TCP port 8088 in
  the available session.
- Result for this candidate: **BLOCKED — real API scenarios not executed**.

The earlier project audit documents a successful connection to vMix
`29.0.0.48`, but it does not replace a release-candidate regression run.

### vMix 25

- vMix 25 is not installed on this machine.
- Result for this candidate: **NOT RUN — separate Windows environment required**.

No vMix 25 or vMix 29 compatibility claim should be attached to this candidate
until the real-vMix matrix in `release-checklist-vmix-25-29.md` is completed.

## Remaining release constraints

- The installer is not signed with a commercial code-signing certificate, so
  Windows SmartScreen may display a warning.
- The portable ZIP remains installable through
  `scripts\install_windows_tester.cmd`.
- The real-vMix matrix and an installer run on a separate Windows PC still need
  to be completed before production certification.
