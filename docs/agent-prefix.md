# Agent prefix — Jaco CMS

## Restate the task before acting
One sentence: what changes, and in which layer (core, domain, api, frontend, docs).
If the layer is unclear, ask instead of guessing.

## Read before write
- Backend: read the domain function you extend AND its API action.
- Frontend: read the model AND the view it belongs to.
- Tests: read one existing test of the same shape before writing a new one.

## Verify at the smallest scope first
1. `php -l <file>` after every PHP edit.
2. One targeted test: `php vendor/bin/phpunit --filter <Name>`.
3. Only then `composer check`.
4. Frontend: `node --check`. QUnit only when the run is required.
Never debug by repeated full runs.

## Tool output discipline
- Never `cat` a file without a line range; use read with offset/limit.
- Prefer grep to locate, then read 20 lines around the match.
- Never print a full JS bundle or a full test suite output.

## Editing discipline
- One edit per intent; verify the anchor string exists first.
- If an edit pattern fails once, read the file and edit by exact line,
  do not retry the same pattern with new escaping.

## Stop conditions
Stop and ask when the requirement has two plausible readings, when a check fails
twice for the same reason, or when a change would touch an invariant in
[AGENTS.md](../AGENTS.md).

## Environment facts (this machine)
- No Chrome: the QUnit runner needs an explicit browser,
  `--chrome="/Applications/Brave Browser.app/Contents/MacOS/Brave Browser"`.
- The Playwright suite cannot run here; write the spec and report it as unverified.
- Throw-away docroots can end up unremovable (`rm: Permission denied` on
  `<docroot>/jaco`, Docker's gRPC FUSE ownership attribute); the location does
  not matter, `$TMPDIR` is affected as well. Do not debug it, report the path
  and let the human remove it with `sudo rm -rf`.
- Stop a dev server when it is done with; a running `php -S` holds its docroot:
  `pkill -f "php -S 127.0.0.1:<port>"`.
