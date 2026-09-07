# CrewFlow Worker Portal (Vue 3 SPA)

The worker-facing app — where the invite link from the Employee module's invitation flow (`crewflow`'s `Modules/Employee`) actually goes, and where a worker signs in afterward. A separate project from `admin-panel` (different persona, different needs), same backend.

## What's here so far

- **Accept invite** (`/accept-invite?token=...&company=...`) — reads both query params (see "why both params" below), shows "You've been invited to join {company}", and lets the worker set their real name/phone/password. Calls `GET /api/invitations/{token}` then `POST /api/invitations/{token}/accept`, both on the Employee module — both public, no login needed yet.
- **Login** (`/login`) — for a worker who already has an account and is returning. Same company-code + email + password pattern as the admin panel (same backend, same auth mechanism).
- **Main app shell** (`AppShell.vue`) — a bottom tab bar (Home / Jobs / Calendar / Chat / Profile), matching a native-app feel since a worker is far more likely to open this on a phone than a desktop.
  - **Home** (`/`) — placeholder; today's shifts, upcoming assignments, and quick actions come next.
  - **Jobs** (`/jobs`) — placeholder; browsing/expressing interest in available shifts comes next.
  - **Calendar** (`/calendar`) — placeholder; a calendar view of assignments comes next.
  - **Chat** (`/chat`) — placeholder; wires into the existing Chat module (direct/group/broadcast, and the automatic per-Event team chat) once built.
  - **Profile** (`/profile`) — an ID card (name + company) and an accordion of sections (tap the `+` to expand in place, no navigating away): My info (Personal details/Skills/Bank, each its own nested accordion), Accounting, Payroll, Documents (Work contracts / My uploads — the latter is fully wired to the existing `GET/POST /api/documents` endpoints, upload included), Share app, Settings (Language/Company/Sign out/Delete account, also nested).

## Building Profile's sections — one at a time, and the one open design question

Per the plan: My info (personal details, address, skills, bank details), Accounting (hours worked per shift/event this month), Payroll (pay calculated from those hours), Documents (contract documents vs. the worker's own uploads), Share app (referral code), Settings (language, company switch, sign out, delete account).

**The one real architectural decision still open**: several of these (skill questions, personal-info fields, document types) need to be *configurable per company* — one company might ask about a driving license (manual vs. automatic), another might not need that at all but wants a different custom question instead. This needs a proper dynamic-fields system on the backend (company-defined field definitions + worker-submitted answers), not hardcoded columns — to be designed before any of these sections gets built for real.

## Why the invite link carries both `token` and `company`

Every API call here needs to know which tenant's subdomain to hit (`{company-code}.crewflow.localhost/api/...`) — but before accepting an invite, the worker has no session yet to read that from. So the company code rides along in the URL itself (`?company=...`), read directly from the route query in `AcceptInviteView` — see `api/invitations.js`, which builds its own base URL from that param rather than going through the normal `client.js` (which depends on the auth store already having a `companyCode`, not yet true at this point).

## Install

1. Bootstrap a fresh Vue 3 project (if you haven't already):
   ```bash
   cd ~/projects/Samples/CrewFlow
   npm create vue@latest
   # name: worker-portal, TypeScript: No, Router: Yes, Pinia: Yes, everything else: your call
   cd worker-portal
   npm install
   npm install axios
   ```
2. Extract this overlay's `src/` folder and `.env.example` into that fresh project — merges with (doesn't replace) the framework's own files. `src/App.vue` and `src/main.js` **will** be replaced (the fresh install's versions are near-empty anyway).
3. Copy `.env.example` to `.env` (adjust `VITE_API_ROOT_DOMAIN` if your local setup differs from `crewflow.localhost:8000`).
4. Run it **on port 5174** — this matches the backend's default `WORKER_PORTAL_URL` (`http://localhost:5174/accept-invite`), so an invite link sent while testing locally actually opens this app:
   ```bash
   npm run dev -- --port 5174
   ```
   (`admin-panel` already uses Vite's default 5173 — the two need different ports to run at the same time.)

## Testing the full invite loop

1. In `admin-panel`, invite a worker by email (`Workers` → `+ Invite worker`).
2. Check `storage/logs/laravel.log` on the backend (with `MAIL_MAILER=log`) for the email, copy the link.
3. Open that link — it should land on `http://localhost:5174/accept-invite?token=...&company=...` and show "You're invited".
4. Fill in the form, submit — you should land on the dashboard, signed in.

## Structure

```
src/
  api/client.js         axios instance; base URL set per-request from the auth store's companyCode (used once logged in)
  api/invitations.js     fetchInvitation()/acceptInvitation() — built on a raw axios call, not client.js, since there's no session yet (see above)
  api/documents.js        fetchMyDocuments()/uploadDocument() — wraps the Employee module's existing document endpoints
  stores/auth.js          Pinia store: companyCode, token, user, login()/logout()/setSession(), persisted to localStorage
  router/index.js         route guard: redirects to /login when unauthenticated (accept-invite and login are public)
  components/layout/AppShell.vue  bottom tab bar wrapping every authenticated page
  components/AccordionItem.vue    reusable expand/collapse item — Profile's sections and their nested sub-sections are all built from this
  views/AcceptInviteView.vue   the invite-completion screen
  views/LoginView.vue          returning-worker sign in
  views/HomeView.vue           the Home tab
  views/JobsView.vue           the Jobs tab
  views/CalendarView.vue       the Calendar tab
  views/ChatView.vue           the Chat tab
  views/ProfileView.vue        the Profile tab — ID card + menu of sub-sections (still placeholders)
  assets/main.css               design tokens — same palette/type as admin-panel, for brand consistency
```

## Design note

Single-column, centered-card layouts for auth screens (not the admin panel's split-screen login), and a bottom tab bar rather than a sidebar once logged in — a worker is far more likely to open this on a phone than a desktop.
