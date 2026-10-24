# Jaco CMS Architecture Overview

Jaco keeps the public site as static HTML while the Jaco application handles inline editing, media, templates, backups, and roles. The website files stay portable and versionable, and the PHP backend handles the editing workflows through file-based storage instead of a database.

```mermaid
flowchart LR
    User[Editor / owner]
    Browser[Browser]

    subgraph PublicSite[Public site]
        HTML[Static HTML pages]
        Markers[data-jaco-* markers]
    end

    subgraph JacoApp[Jaco app]
        Frontend[Frontend UI\nJS + editing widgets]
        API[PHP API\nbackend/api]
        Domain[Domain logic\ncontent, auth, settings]
        Core[Core services\nconfig, HTTP, payload]
    end

    subgraph Storage[File-based storage]
        Pages[Page files]
        Templates[Templates / fragments]
        Media[Media uploads]
        Backups[Backups]
        State[jaco-data / runtime / logs]
    end

    subgraph Auth[Access control]
        Accounts[User accounts]
        Roles[Roles / permissions]
        JWT[JWT + optional TOTP]
    end

    User --> Browser
    Browser --> Frontend
    Frontend -->|AJAX / JSON| API
    API --> Domain
    Domain --> Core

    Domain --> Accounts
    Domain --> Roles
    Domain --> JWT
    Domain --> Pages
    Domain --> Templates
    Domain --> Media
    Domain --> Backups
    Domain --> State

    HTML --> Browser
    Pages --> HTML
    Markers --> Frontend

    WebServer[Apache / Nginx + PHP] --> Browser
    WebServer --> JacoApp
    WebServer --> PublicSite
```

## Core runtime

- The public site remains plain HTML and can be served from a normal static hosting setup.
- Markers such as `data-jaco-*` identify editable regions that the frontend can manipulate inline.
- The PHP backend exposes JSON endpoints for page content, settings, templates, media, and system actions.

## Storage and business logic

- The domain layer applies the CMS rules: backups before updates, role checks, and page/settings workflows.
- Page files, templates, media, and backups are stored on disk rather than in a database, which keeps the system portable and easy to version.
- Access control is enforced through user accounts, roles, and JWT-based login with optional second-factor authentication.

## Authentication and authorization flow

```mermaid
flowchart TD
    User[User tries to log in] --> Login[Login request]
    Login --> Validate[Validate credentials]
    Validate --> RoleCheck{Role known and allowed?}

    RoleCheck -- No --> Reject[Reject login]
    RoleCheck -- Yes --> IssueJWT[Issue JWT / session]
    IssueJWT --> Routes[Protected routes check scope]

    Routes --> Allowed{Action allowed for role?}
    Allowed -- Yes --> Action[Edit page / template / media]
    Allowed -- No --> Block[403 / disabled navigation]

    Action --> Backup[Create backup before write]
    Backup --> Persist[Write to file-based storage]
```

This second diagram focuses on the user-access path: credentials are validated, the role is checked, a JWT/session is issued, and each protected action is gated by permission checks before any write is committed.

## Permission split by feature area

```mermaid
flowchart TB
    subgraph Editor[Editor role]
        E1[Content pages]
        E2[Media library]
        E3[Page and block templates]
        E4[Password change for own account]
    end

    subgraph Admin[Admin role]
        A1[Design / CSS]
        A2[Backups and restore]
        A3[Site templates]
        A4[System pages]
        A5[Navigation and global settings]
    end

    User[Authenticated user] --> Route[Route check by purpose]
    Route --> Editor
    Route --> Admin

    E1 --> ContentEdit[Edit page content]
    E2 --> MediaOps[Upload and manage media]
    E3 --> TemplateOps[Create or adjust templates]
    E4 --> SelfPassword[Change own password]

    A1 --> DesignOps[Modify design assets]
    A2 --> BackupOps[Restore and manage backups]
    A3 --> SiteTemplateOps[Manage site template fragments]
    A4 --> SystemOps[Status and system diagnostics]
    A5 --> AdminSettings[Admin-level configuration]
```

This third diagram reflects the role split described in the project: editors can work on content and templates, while administrators retain design, backup, system, and template-management access. Both roles can change their own password, but only the relevant role receives access to a given feature group.
