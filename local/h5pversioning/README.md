# local_h5pversioning

`local_h5pversioning` is a Moodle local plugin that adds controlled version tracking for H5P content in the Content Bank.

The main idea is simple:
- Track one configured "dev" course Content Bank as the source of truth.
- Create a snapshot when H5P bytes actually change.
- Skip duplicate saves that produce the same file hash.
- Keep an auditable event log so you can see what happened and why.

## What Problem This Solves

Many Moodle teams want a central H5P development workflow, but do not want every course-level remix to pollute version history.

This plugin gives you:
- A clean version timeline for one chosen development bank.
- Snapshot files stored in Moodle-managed storage.
- Decision-level logs (`snapshot_created`, `duplicate_skipped`, `ignored_non_h5p`, etc.).

## What This Plugin Tracks

The plugin listens to Content Bank lifecycle events:
- `\core\event\contentbank_content_created`
- `\core\event\contentbank_content_uploaded`
- `\core\event\contentbank_content_updated`

For each in-scope event, it:
1. Resolves the Content Bank item.
2. Verifies it is H5P (`contenttype_h5p`).
3. Reads file hash.
4. Compares against latest stored version for that item.
5. Creates new snapshot only if hash changed.

## What This Plugin Intentionally Does Not Do

- It does not version every H5P action across the entire site.
- It does not force teachers into one picker mode (copy vs alias) by itself.
- It does not replace Moodle backup/import workflows.

This is intentional. The plugin is designed for "central dev bank" governance.

## Architecture (Quick)

- Event observer wiring: `db/events.php`
- Observer entrypoint: `classes/observer.php`
- Versioning logic: `classes/versioning_service.php`
- Event report page: `log.php`
- Snapshot report/download page: `versions.php`
- File serving callback: `lib.php` (`local_h5pversioning_pluginfile`)
- DB schema: `db/install.xml`, upgrades in `db/upgrade.php`

## Installation

Plugin type: `local`  
Folder path in Moodle codebase: `local/h5pversioning`

Install via Moodle UI:
1. Site administration -> Plugins -> Install plugins
2. Upload ZIP containing top-level folder `h5pversioning/`
3. Complete upgrade at Site administration -> Notifications

## Configuration

Go to:
`Site administration -> Plugins -> Local plugins -> H5P versioning`

Set:
- `Enable versioning monitor`: on/off switch
- `Monitored dev course ID`: the only course whose Content Bank is versioned

Without a valid monitored course ID, versioning remains inactive.

## Daily Workflow

Recommended model:
1. Dev team edits official H5P in the monitored dev course Content Bank.
2. Plugin creates snapshots on real content changes.
3. Teachers consume content in their own courses (usually as copies).
4. Course-local edits do not pollute central history.

## Reports

Two admin pages are included:
- `H5P versioning event log`: raw events + decisions + links to versions
- `H5P version snapshots`: manifest of created versions + download links

Access is protected by capability:
- `local/h5pversioning:viewreports`

Assign this only to your dev/admin roles.

## Status

This project is intended for controlled institutional workflows and is currently optimized for clarity and governance over broad automation.
