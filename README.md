# Symfony Dashboard — All 6 Architecture Patterns

A complete **Domain-Driven Design** Symfony 7.44 LTS application with all 6 patterns fully implemented:

| Pattern | Status | Implementation |
|---|---|---|
| DDD | ✅ Full | 4-layer architecture, Entities, VOs, Specs, Domain Services |
| CQRS | ✅ Full | Separate command/query buses, dedicated Read Model DTOs, DBAL read repo |
| Event-Driven | ✅ Full | 3 reactive event handlers on event.bus (alert, cache, metrics) |
| Async Processing | ✅ Full | Domain events routed to `async` transport, doctrine queue, retry strategy |
| Caching Layer | ✅ Full | PSR-6 pool (filesystem dev / Redis prod), TTL=60s, event-driven invalidation |
| Optimised Read Models | ✅ Full | DBAL flat projections, `SiteRecordReadModel` + `DashboardSummaryReadModel` DTOs |

---

## Requirements

- PHP 8.2+
- Composer
- SQLite (`php-sqlite3` or `pdo_sqlite`)
- Optional for prod: Redis, RabbitMQ/Redis for async transport

---

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Create database + run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# 3. Start the dev server
symfony server:start
# or: php -S localhost:8000 -t public/

# 4. (Optional) Start async worker for domain events
php bin/console messenger:consume async --limit=50 -vv
```

Open http://localhost:8000

---

## Architecture Overview

```
src/
├── Dashboard/
│   ├── Domain/                          ← Pure PHP — zero Symfony deps
│   │   ├── Entity/SiteRecord.php        ← Aggregate Root
│   │   ├── ValueObject/SiteUrl.php      ← Validated URL
│   │   ├── ValueObject/SiteStatus.php   ← Enum-like status
│   │   ├── Event/SiteRecordCreated.php  ← Domain event
│   │   ├── Event/SiteRecordUpdated.php
│   │   ├── Event/SiteRecordDeleted.php
│   │   ├── Repository/SiteRecordRepositoryInterface.php
│   │   ├── Specification/SiteRecordSpecifications.php
│   │   └── Service/DashboardStatisticsService.php
│   │
│   ├── Application/                     ← Use cases — orchestrates domain
│   │   ├── Command/Create|Update|DeleteSiteRecordCommand.php
│   │   ├── Handler/Create|Update|DeleteSiteRecordHandler.php   ← write side
│   │   ├── Query/GetDashboardQuery.php
│   │   ├── Query/ReadModel/
│   │   │   ├── SiteRecordReadModel.php       ← flat DTO (CQRS read projection)
│   │   │   └── DashboardSummaryReadModel.php ← statistics DTO
│   │   └── QueryHandler/GetDashboardQueryHandler.php  ← read side + cache
│   │
│   ├── Infrastructure/                  ← Symfony / Doctrine implementations
│   │   ├── Repository/
│   │   │   ├── DoctrineSiteRecordRepository.php  ← ORM write repo
│   │   │   └── DashboardReadRepository.php        ← DBAL read projections
│   │   ├── Cache/
│   │   │   └── DashboardCacheInvalidator.php      ← PSR-6 invalidation
│   │   ├── Doctrine/Middleware/
│   │   │   └── SlowQueryLogger*.php               ← DBAL slow query logging
│   │   └── EventListener/
│   │       ├── AlertOnSiteErrorHandler.php        ← event.bus async handler
│   │       ├── InvalidateCacheOnSiteRecordChanged.php
│   │       ├── MetricsOnDomainEventHandler.php
│   │       └── RequestResponseLogSubscriber.php   ← HTTP logging
│   │
│   └── Presentation/
│       └── Controller/DashboardController.php
│
└── Shared/
    └── Infrastructure/
        ├── Messenger/
        │   ├── LoggingMiddleware.php       ← application channel
        │   └── PerformanceMiddleware.php   ← performance channel + Stopwatch
        └── EventListener/
            └── DomainEventAuditLogger.php  ← domain channel
```

---

## Pattern Details

### CQRS
- `command.bus` → `CreateSiteRecordHandler` → writes via ORM `DoctrineSiteRecordRepository`
- `query.bus`   → `GetDashboardQueryHandler` → reads via DBAL `DashboardReadRepository` + cache
- Query handler returns `SiteRecordReadModel[]` (flat DTO) — **never** domain `SiteRecord` entities

### Event-Driven Architecture
Three reactive handlers on `event.bus`, all async:

| Handler | Trigger | Action |
|---|---|---|
| `AlertOnSiteErrorHandler` | `SiteRecordUpdated` (status=error) | Log critical / send alert |
| `InvalidateCacheOnSiteRecordChanged` | Any site event | Invalidate read cache |
| `MetricsOnDomainEventHandler` | Any site event | Emit structured metric log |

### Async Processing
```bash
# Start the worker — processes domain events from the queue
php bin/console messenger:consume async --limit=100 --time-limit=3600 -vv

# Retry failed messages
php bin/console messenger:failed:retry

# Inspect failed queue
php bin/console messenger:failed:show
```

### Caching Layer
- Pool: `dashboard.cache` (filesystem in dev, Redis in prod)
- TTL: 60 seconds
- Keys: `dashboard.records.all`, `dashboard.records.active`, etc.
- Invalidation: triggered by `DashboardCacheInvalidator` after every write command
  AND by `InvalidateCacheOnSiteRecordChanged` async event handler

### Optimised Read Models
- `DashboardReadRepository` uses raw DBAL `createQueryBuilder()` — zero ORM entity hydration
- Returns `SiteRecordReadModel` (flat `readonly` DTO)
- `DashboardSummaryReadModel` computes statistics from DTOs without touching the domain layer

---

## Log Files

| File | Channel | Content |
|---|---|---|
| `dev.log` | main | Errors only (fingers_crossed) |
| `dashboard.log` | dashboard | HTTP req/res, timing (JSON) |
| `dashboard_performance.log` | performance | Handler timings, slow SQL (JSON) |
| `domain.log` | domain | Domain events, alerts, metrics (JSON) |
| `application.log` | application | Command/query lifecycle (JSON) |

---

## Running Tests

```bash
php bin/phpunit
```

Test coverage includes: Entity, Value Objects, Specifications, Statistics Service,
Read Models, Cache Invalidator, Logging Middleware, Performance Middleware,
and Event-driven alert handler.
