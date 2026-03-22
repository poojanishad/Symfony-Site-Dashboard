# Symfony Dashboard — Backend

REST API backend built with **Symfony 7 + PHP 8.2** following DDD, CQRS, and Event-Driven architecture. Serves 100k+ site records with caching and async processing.

---

## Requirements

- PHP 8.2+
- Composer
- MySQL (XAMPP recommended)
- Symfony CLI — https://symfony.com/download

---

## Setup

### 1. Install CORS bundle

```bash
composer require nelmio/cors-bundle
```

### 2. Install all dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
```

Edit `.env`:

```dotenv
APP_ENV=dev
APP_SECRET=your_secret_here
DATABASE_URL="mysql://root:@127.0.0.1:3306/symfony_dashboard_2?charset=utf8mb4"
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=true
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

> Generate APP_SECRET:
> ```bash
> php -r "echo bin2hex(random_bytes(16));"
> ```

### 4. Create database

```bash
php bin/console doctrine:database:create
```

### 5. Run migrations

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Seed 100,000+ records

```bash
php bin/console dashboard:seed --count=100000
```

> This may take 1-2 minutes to insert 100k records.

---

## Start Server

```bash
symfony server:start
```

Or without Symfony CLI:

```bash
php -S 127.0.0.1:8000 -t public/
```

API available at: **http://127.0.0.1:8000**

---

## Full Setup — All Steps at Once

```bash
composer require nelmio/cors-bundle
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console dashboard:seed --count=100000
symfony server:start
```

---

## API Endpoints

### Get site records (paginated)
```
GET /api/site-records
```

| Param    | Type   | Default    | Example               |
|----------|--------|------------|-----------------------|
| page     | int    | 1          | ?page=2               |
| perPage  | int    | 50         | ?perPage=25           |
| status   | string | (all)      | ?status=active        |
| country  | string | (all)      | ?country=IN           |
| category | string | (all)      | ?category=ecommerce   |
| search   | string | (empty)    | ?search=amazon        |
| sortBy   | string | created_at | ?sortBy=page_views    |
| sortDir  | string | DESC       | ?sortDir=ASC          |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "url": "https://example.com",
      "name": "Example Site",
      "status": "active",
      "page_views": 12000,
      "unique_visitors": 4500,
      "bounce_rate": 42.5,
      "avg_session_duration": 185.0,
      "country": "IN",
      "category": "ecommerce",
      "created_at": "2024-01-01 00:00:00",
      "updated_at": "2024-01-01 00:00:00"
    }
  ],
  "meta": {
    "total": 100000,
    "page": 1,
    "per_page": 50,
    "total_pages": 2000
  }
}
```

---

## Architecture
```

| Pattern          | Implementation                                 |
|------------------|------------------------------------------------|
| DDD              | Entities, Value Objects, Domain Events         |
| CQRS             | command.bus write / query.bus read             |
| Event-Driven     | 3 async handlers on event.bus                  |
| Async Processing | Doctrine Messenger queue                       |
| Caching          | PSR-6 pool, TTL 60s, event-driven invalidation |
| Optimised Reads  | Raw DBAL, flat DTOs — no ORM hydration         |


## Run Unit Tests

```bash
php bin/phpunit
```

---

## Log Files

| File                                | Content                      |
|-------------------------------------|------------------------------|
| var/log/dev.log                     | General errors               |
| var/log/dashboard.log               | HTTP requests & timing       |
| var/log/domain.log                  | Domain events & alerts       |
| var/log/application.log             | Command/query lifecycle      |
| var/log/dashboard_performance.log   | Slow queries & handler times |

---

## Clear Cache
```bash
php bin/console cache:clear
```
