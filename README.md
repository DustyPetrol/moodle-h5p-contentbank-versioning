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

```mermaid
flowchart TB
    %% ---------- LAYERS ----------
    subgraph L1["Layer 1: User + Moodle Core"]
        A["Content Bank UI<br/>Create / Upload / Update H5P"]
        B["Core Events API<br/>contentbank_content_created / uploaded / updated"]
        A --> B
    end

    subgraph L2["Layer 2: Plugin Processing"]
        C["Observer<br/>local_h5pversioning/classes/observer.php"]
        D["Versioning Service<br/>local_h5pversioning/classes/versioning_service.php"]
        E{"Scope check<br/>Monitored course/context?"}
        G{"H5P check<br/>contenttype_h5p?"}
        H["Resolve Content Bank item<br/>Read current file hash"]
        I{"Hash changed<br/>vs latest version?"}
        F["Ignore event"]
        J["Decision: duplicate_skipped"]
        K["Create snapshot file<br/>Moodle File API<br/>component=local_h5pversioning<br/>filearea=snapshot"]
        M["Decision: snapshot_created"]

        B --> C --> D --> E
        E -- "No" --> F
        E -- "Yes" --> G
        G -- "No" --> F
        G -- "Yes" --> H --> I
        I -- "No" --> J
        I -- "Yes" --> K --> M
    end

    subgraph L3["Layer 3: Persistence + Reports"]
        N["Event Log Table<br/>local_h5pversioning_evtlog"]
        L["Version Manifest Table<br/>local_h5pversioning_version"]
        P["Event Report UI<br/>log.php"]
        O["Version Report UI<br/>versions.php"]

        J --> N
        M --> N
        K --> L
        N --> P
        L --> O
    end

    %% ---------- STYLES ----------
    classDef ui fill:#e8f1ff,stroke:#2b6cb0,stroke-width:1.5px,color:#102a43;
    classDef core fill:#edfdf6,stroke:#2f855a,stroke-width:1.5px,color:#1c4532;
    classDef logic fill:#fffbea,stroke:#b7791f,stroke-width:1.5px,color:#744210;
    classDef decision fill:#f0fff4,stroke:#2f855a,stroke-width:2px,color:#1c4532;
    classDef ignore fill:#fff5f5,stroke:#c53030,stroke-width:1.5px,color:#742a2a;
    classDef storage fill:#f7fafc,stroke:#4a5568,stroke-width:1.5px,color:#1a202c;
    classDef report fill:#faf5ff,stroke:#6b46c1,stroke-width:1.5px,color:#44337a;

    class A ui;
    class B core;
    class C,D,H,K,J,M logic;
    class E,G,I decision;
    class F ignore;
    class N,L storage;
    class O,P report;
    ```

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
