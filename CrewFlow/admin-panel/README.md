# CrewFlow Admin Panel (Vue 3 SPA)

The Company Admin/Dispatcher-facing web app. Talks to `crewflow`'s tenant API (`{company-code}.crewflow.localhost:8000/api`).

## What's here so far

- **Login flow**: `LoginView` → `useAuthStore` (Pinia, persisted to `localStorage`) → `DashboardView`.
- **Workers** (`/workers`): the dispatcher-facing search directory — find who's qualified, in the right branch, and free at a given day/time, for staffing a shift. Distinct from Users below: any Dispatcher can use this, not just Company Admin.
- **Invite worker** (`/workers/invite`) — the primary path now: an admin/dispatcher provides only an email; the worker sets their own name/phone/password via the link they receive. Just calls `POST /api/workers/invite`.
- **Add worker** (`/workers/new`) — the older manual flow (still linked from the invite page, for e.g. importing existing employee data): creates the account and *personal* record only (no contract) — chains `POST /api/auth/register` → `PUT /api/users/{user}/worker` → `PUT /api/users/{user}/employment` → qualifications/availability. Redirects to the worker's detail page afterward.
- **Worker detail** (`/workers/:userId`): the personal record, employment relationship, and full contract history for one worker, plus a form to add a new contract. Deliberately a separate page from "Add worker" — a worker isn't defined by a contract, and can have several over time (see the Employee module's README for the full `Worker`/`CompanyWorker`/`EmploymentContract` rationale).
- **Users** (`/users`): lists every registered user and lets an admin grant/revoke roles. This is an access-control tool, not a staffing tool — see Workers instead for "who can I assign to this shift".
- **Roles** (`/roles`): manage roles themselves — create a role, edit its permissions, delete non-system roles.

Split Users into two separate pages (rather than one combined page) specifically so the list stays usable once a company has a large roster — the Roles panel doesn't get pushed down the page by a long user table. All talk directly to existing backend endpoints.

Everything else (shifts, events, etc.) comes next.

## Note on the Worker / CompanyWorker / EmploymentContract split

A worker's contract terms are not a permanent attribute of the person — someone might be `Geringfügig` today and sign a different contract months later. So the frontend mirrors the backend's three-layer split exactly:

- **Worker** — personal facts (name, DOB, address) + work authorization. Edited on the worker detail page's left panel.
- **CompanyWorker** — the employment relationship (status, branch, employee number, night-shift preference). Edited on the right panel.
- **EmploymentContract** — full contract history, many rows per worker over time. Its own section below, with a "+ New contract" form and a table of every past/current contract.

`contract_type` has 3 values (`employment_contract`/`free_service_contract`/`work_contract`, shown with their German labels), `work_time_model` has 3 (`full_time`/`part_time`/`casual`), and `is_marginal` (Geringfügig) is its own checkbox — deliberately independent of `work_time_model`, since a worker can be both `part_time` and marginal at once. There's no duration dropdown — permanent vs. fixed-term is shown/derived from whether `end_date` is set (`is_permanent` comes pre-computed from the API).

## Note on "night shift" preference

`works_night_shifts` lives on the employment relationship (`CompanyWorker`), not the worker's personal record — declared explicitly, not inferred from availability time ranges. A dispatcher assigning a night shift needs a direct, filterable yes/no (the Workers page's "Works night shifts" filter), not to reason about which of a worker's availability slots happen to cross midnight.

## Note on the "Assignable right now" filter

The Workers page's `eligible=1` filter matches the exact rule Shift's `AssignmentController` enforces at assignment time: active `Worker.status`, valid/not-required work authorization, active `CompanyWorker.status`, and at least one currently-active contract. Useful for a dispatcher who wants to skip straight to "who can I actually assign today".

## Install

1. Bootstrap a fresh Vue 3 project (if you haven't already):
   ```bash
   cd ~/projects/Samples/CrewFlow
   npm create vue@latest
   # name: admin-panel, TypeScript: No, Router: Yes, Pinia: Yes, everything else: your call
   cd admin-panel
   npm install
   npm install axios
   ```
2. Extract this overlay's `src/` folder and `.env.example` into that fresh project — merges with (doesn't replace) the framework's own files. `src/App.vue` and `src/main.js` **will** be replaced (the fresh install's versions are near-empty anyway).
3. Copy `.env.example` to `.env` (adjust `VITE_API_ROOT_DOMAIN` if your local setup differs from `crewflow.localhost:8000`).
4. Run it:
   ```bash
   npm run dev
   ```

## How multi-tenancy works on the frontend

Every company has its own subdomain (e.g. `acme2024.crewflow.localhost:8000`) — there's no single shared API URL. The login form asks for **company code** in addition to email/password; `useAuthStore.login()` builds the tenant's base URL from it (`buildBaseUrl()` in `src/api/client.js`) and persists both the code and the token together, so every subsequent request knows which company's API to call.

## If you hit a CORS error

`crewflow`'s `config/cors.php` should already allow this by default (Laravel ships with `allowed_origins: ['*']` for `api/*`). If you've customized it, make sure `http://localhost:5173` (Vite's dev server) is allowed.

## Auth mechanism

Token-based (Sanctum personal access tokens via `Bearer` header), **not** cookie/CSRF SPA auth — matches how `crewflow`'s `AuthController::login()` already works (returns a plain-text token, no session/cookie dance needed). The token lives in `localStorage` via the Pinia store; there's no httpOnly-cookie protection against XSS token theft, which is an accepted tradeoff for this stage — revisit if this ever needs hardening for production.

## Structure

```
src/
  api/client.js         axios instance; base URL set per-request from the auth store's companyCode
  api/authorization.js  thin wrapper around the users/roles/permissions endpoints
  api/workers.js         thin wrapper around worker/employment/contract/directory/qualifications/branches endpoints
  stores/auth.js         Pinia store: companyCode, token, user, login()/logout(), persisted to localStorage
  router/index.js        route guard: redirects to /login when unauthenticated, and away from /login when already signed in
  components/layout/AppShell.vue  sidebar (with icons) + topbar wrapping every authenticated page
  views/LoginView.vue     the login screen
  views/DashboardView.vue placeholder post-login screen
  views/WorkersView.vue   dispatcher-facing worker search (qualification/branch/contract/availability filters)
  views/CreateWorkerView.vue  account + personal record + employment relationship + qualifications + availability (NO contract — see below)
  views/WorkerDetailView.vue  one worker's full profile: personal details, employment relationship, and contract history/creation
  views/UsersView.vue     user list + role assignment
  views/RolesView.vue     role/permission management
  assets/main.css         design tokens (colors, fonts) shared globally
```

## Known simplification

The "Users" nav link is always visible regardless of the signed-in user's own permissions — if they lack `users.manage`, the page just shows an error banner (the API call gets a 403). Hiding the nav link itself based on `auth.permissions` is a reasonable next hardening step, not done yet.
