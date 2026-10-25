# Jaco CMS Architecture Overview

This project is a flat-file CMS: the public site remains static HTML, while the Jaco application manages editable regions, user accounts, media, templates, and backups without a database.

```mermaid
flowchart LR
    User[Website owner / editor]
    Browser[Browser]

    subgraph PublicSite[Public website root]
        HTML[Static HTML pages]
        Markers[data-jaco-* edit markers]
    end

    subgraph JacoApp[Jaco application]
        Frontend[Frontend UI\nMithril/JS + editor widgets]
        API[PHP API layer\nbackend/api]
        Domain[Domain logic\ncontent, auth, routing, settings]
        Core[Core services\nconfig, HTTP, payload, auth helpers]
    end

    subgraph Data[File-based storage]
        Pages[Editable page files]
        Templates[Site templates / fragments]
        Media[Media library uploads]
        Backups[Timestamped backups]
        State[jaco-data / runtime / logs]
    end

    subgraph Auth[Access control]
        Users[User accounts]
        Roles[Roles: admin / editor\nreadOnly + password rules]
        JWT[JWT login + optional TOTP]
    end

    User --> Browser
    Browser --> Frontend
    Frontend -->|AJAX / JSON| API
    API --> Domain
    Domain --> Core

    Domain --> Users
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

    WebServer[Apache / Nginx / PHP runtime] --> Browser
    WebServer --> JacoApp
    WebServer --> PublicSite

    classDef app fill:#dfefff,stroke:#3b82f6,stroke-width:1px;
    classDef data fill:#e8f5e9,stroke:#22c55e,stroke-width:1px;
    classDef auth fill:#fff3cd,stroke:#f59e0b,stroke-width:1px;
    classDef user fill:#f3e8ff,stroke:#8b5cf6,stroke-width:1px;

    class Frontend,API,Domain,Core app;
    class Pages,Templates,Media,Backups,State,HTML data;
    class Users,Roles,JWT auth;
    class User,Browser,WebServer user;
```

## How it fits together

- The public site is plain HTML and remains portable and static.
- Jaco marks editable regions with `data-jaco-*` attributes, which the frontend reads and edits inline.
- The PHP backend exposes JSON APIs for page content, media, settings, templates, and user actions.
- The domain layer handles business rules such as authorization, backup creation, and page updates.
- Files on disk become the system of record: pages, templates, media, and backups are stored in the filesystem instead of a database.
- The web server serves the public site and routes requests to the Jaco application, which protects internal/admin areas and manages content.

## Key design characteristics

- Flat-file architecture: no database required.
- Inline editing directly on the live site.
- Backup-first change flow: each mutation keeps a timestamped backup.
- Role-based access control for admin/editor behavior.
- PHP-based runtime with the application living beside the website content.
