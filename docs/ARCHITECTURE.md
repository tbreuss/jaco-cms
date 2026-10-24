# Jaco CMS Architecture Overview

This document is intentionally a system overview, not a second installation guide or feature reference. It complements the rest of the docs in `docs/` by showing how the main runtime parts fit together and where the role and write boundaries sit.

## 1) System overview

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

## 2) Authentication and authorization flow

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

This flow shows the decision path after login: the role is checked before a session is granted, and route-level permission checks determine whether a write is allowed.

## 3) Permission split by feature area

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

This reflects the reduced role model: editors can handle content workflows, while administrators keep system, design, backup and global configuration responsibilities. Both roles may change their own password.

## 4) Page save flow with automatic backups

```mermaid
flowchart LR
    Browser[Browser form or editor action] --> Frontend[Frontend validates request]
    Frontend --> API[PHP API endpoint]
    API --> Domain[Domain logic: page update]
    Domain --> Backup[Create timestamped backup]
    Backup --> Write[Write new HTML or config data]
    Write --> Persist[Persist to page file / template / media resource]
    Persist --> Notify[Return success response]
    Notify --> Refresh[Refresh page state in UI]

    Domain -->|Validation error| Error[Return validation / auth error]
    Error --> Browser
```

The write path is intentionally safety-first: a backup is created before the new content is written to disk.

## 5) File-system and deployment layout

```mermaid
flowchart TB
    subgraph WebRoot[Website root]
        Site[Static HTML pages]
        JacoDir[jaco/\napplication code]
        DataDir[jaco-data/\nbackups, templates, runtime]
        MediaDir[media/\nuploads]
    end

    Server[Apache / Nginx + PHP] --> WebRoot
    Site --> Browser[Public visitors]

    JacoDir --> App[CMS logic and API]
    DataDir --> Templates[Templates and backups]
    MediaDir --> Assets[Images, PDFs, uploads]

    App -->|read/write| Site
    App -->|read/write| DataDir
    App -->|read/write| MediaDir
```

This layout shows the split between the public site and the private CMS workspace. The application and the editable data live beside the site, but are separated from normal public browsing by design.

## 6) Role capability matrix

```mermaid
flowchart LR
    subgraph Editor[Editor]
        E1[Content editing]
        E2[Media management]
        E3[Page templates]
        E4[Own password change]
    end

    subgraph Admin[Admin]
        A1[Design and CSS]
        A2[Backups / restore]
        A3[Site templates]
        A4[System pages]
        A5[Global configuration]
        A6[Own password change]
    end

    E1 --> Yes1[Yes]
    E2 --> Yes2[Yes]
    E3 --> Yes3[Yes]
    E4 --> Yes4[Yes]

    A1 --> Yes5[Yes]
    A2 --> Yes6[Yes]
    A3 --> Yes7[Yes]
    A4 --> Yes8[Yes]
    A5 --> Yes9[Yes]
    A6 --> Yes10[Yes]
```

This is a compact summary of who can do what. It complements the more operational diagrams without repeating the installation or feature documentation already present elsewhere in `docs/`.

## Scope and boundaries

This page is intentionally narrow in scope:

- it explains how the moving parts fit together
- it highlights permission boundaries and write safety
- it visualizes the static-site + CMS split

It does not repeat the installation, contributor workflow, or product feature guides already covered in the rest of the documentation.
