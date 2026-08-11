# Client Module

The company's own customers — e.g. a building needing security guards, a family needing a private tutor. **Not** to be confused with `Company` (the tenant itself, managed in the Tenancy module) — a `Client` here is who the tenant company performs work *for*.

Split out of the Organization module (previously `Branch` + `Client` lived together there).

## Install

1. Place this folder at `Modules/Client`, `php artisan module:enable Client`, `composer dump-autoload`.
2. Migration lives in `database/tenant-migrations/` (runs only via `php artisan tenants:migrate` — see Authentication's README for the full explanation).

## Permission used

`clients.manage` — already exists in Authorization's seeded permission list, granted to `Company Admin` by default. Viewing the client list (`GET /api/clients`) only requires being an authenticated user of the company; creating/editing/deleting requires the permission.

## Endpoints

```
GET    /api/clients
POST   /api/clients            { name, type?, default_contact_name?, default_contact_phone?, default_address? }   [clients.manage]
PUT    /api/clients/{client}                                                                                        [clients.manage]
DELETE /api/clients/{client}                                                                                        [clients.manage]
```
