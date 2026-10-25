# Jaco CMS Architecture Overview

This document is a system overview for the Jaco CMS. It complements the rest of the documentation in `docs/` by showing the runtime structure, the permission model, and the write path without repeating installation, feature, or development guidance.

## Runtime structure

```mermaid
flowchart LR
    User[Editor / owner]
    Browser[Browser]

    subgraph PublicSite[Public site]
        HTML[Static HTML files]
        Markers[data-jaco-* markers]
    end

    subgraph JacoApp[Jaco app]
        Frontend[Frontend UI]
        API[PHP API]
        Domain[Domain logic]
        Core[Shared core services]
    end

    subgraph Storage[Filesystem storage]
        Pages[Page files]
        Templates[Templates]
        Media[Media uploads]
        Backups[Backups]
        Runtime[jaco-data / runtime]
    end

    User --> Browser
    Browser --> Frontend
    Frontend -->|AJAX / JSON| API
    API --> Domain
    Domain --> Core

    Domain --> Pages
    Domain --> Templates
    Domain --> Media
    Domain --> Backups
    Domain --> Runtime

    HTML --> Browser
    Pages --> HTML
    Markers --> Frontend

    Server[Apache / Nginx + PHP] --> Browser
    Server --> JacoApp
    Server --> PublicSite
```

Jaco keeps the public site as plain HTML while the application handles inline editing, media files, templates, backups, and account access. The PHP backend and domain layer work on top of a file-based storage model, rather than a database.

## Authentication and authorization flow

```mermaid
flowchart TD
    User[Login attempt] --> Validate[Validate credentials]
    Validate --> RoleCheck{Role valid?}
    RoleCheck -- No --> Reject[Reject login]
    RoleCheck -- Yes --> Session[Issue JWT / session]
    Session --> RouteCheck[Check route permissions]
    RouteCheck --> Allowed{Action allowed?}
    Allowed -- No --> Block[403 / disabled nav]
    Allowed -- Yes --> Action[Edit page / media / template]
    Action --> Backup[Create backup before update]
    Backup --> Write[Write file changes]
```

The role is part of the authentication boundary: an unknown or missing role is not silently downgraded. The route layer then decides whether the user can execute a particular action.

## Role split

```mermaid
flowchart TB
    subgraph Editor[Editor]
        E1[Content edits]
        E2[Media management]
        E3[Page templates]
        E4[Own password]
    end

    subgraph Admin[Admin]
        A1[Design / CSS]
        A2[Backups / restore]
        A3[Site templates]
        A4[System pages]
        A5[Global config]
        A6[Own password]
    end

    Route[Permission check by purpose] --> Editor
    Route --> Admin
```

The system reduces access to two main roles. Editors work on content and media flows, while administrators retain design, backup, template, and system-level capabilities. Both roles can update their own password.

## Write path and backup safety

```mermaid
flowchart LR
    UI[Editor action] --> API[API endpoint]
    API --> Domain[Domain validation]
    Domain --> Backup[Create timestamped backup]
    Backup --> Persist[Write new file state]
    Persist --> Notify[Return success]
    Notify --> Refresh[Refresh UI]
```

Every write is treated as a state-changing operation. A backup is created before the new file state is persisted, which keeps content recovery straightforward and reduces accidental loss.

## File-system layout

```mermaid
flowchart TB
    subgraph WebRoot[Website root]
        Site[HTML pages]
        Jaco[jaco/ application]
        Data[jaco-data/ templates + backups]
        Media[media/ uploads]
    end

    Server[Web server + PHP] --> WebRoot
    Jaco --> App[CMS logic]
    Data --> Templates[Template + backup storage]
    Media --> Assets[Uploaded files]
```

This layout separates public-facing static HTML from the private CMS workspace: application code, backups, templates, and uploads remain in structured directories next to the site rather than in a database server.

## Scope boundary

This document intentionally covers:

- runtime composition
- login and authorization flow
- role-based feature boundaries
- backup-before-write behavior
- filesystem layout

It intentionally does not duplicate:

- installation steps
- contributor workflow
- feature-by-feature product documentation
- menu structure
- glossary or constraints material already covered elsewhere in `docs/`
