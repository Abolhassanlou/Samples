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
  - **Profile** (`/profile`) — an ID card (name + company) and an accordion of sections (tap the `+` to expand in place, no navigating away):
    - **My info**: *Personal details* (fixed baseline fields — first/last name and phone (`PUT /api/auth/me`, since phone lives on `User`/Authentication, not `Worker`), date of birth, gender, marital status, nationality (country dropdown), native language (language dropdown), social security number (exactly 10 digits), German level, other languages spoken — plus any company-added `personal_info` questions appended below them; first/last name, phone, DOB, gender, nationality, and SSN are marked required on this form specifically, since the shared `PUT /api/users/{user}/worker` endpoint can't enforce that without breaking the other two forms below, which send different partial payloads to the same endpoint), *Address* (street/house number split, postal code, city, country dropdown, residence type), *Bank details* (bank name, account holder — a note asks it to match the worker's own name, not backend-enforced — IBAN, BIC), *Skills* (company-configurable questions, `category: skill`), *Personal documents* (a checklist — one upload slot per type, not a single shared dropdown+file — covering photo, passport, insurance card front/back, work permit front/back, Meldezettel, etc.).
    - **Accounting** / **Payroll** — still placeholders.
    - **Documents**: *Work contracts* (full history, sign a `pending_signature` one, download the attached file) and *My uploads* (job/event-related documents — a different type list than Personal documents above).
    - **Share app**, **Settings** (Language/Company/Sign out/Delete account) — still placeholders.

## The dynamic-fields system this all builds on

Company-configurable questions (Personal details/Skills) and document types (Personal documents/My uploads) are **not** hardcoded — the Employee module's `CustomFieldDefinition`/`CustomFieldAnswer`/`CustomDocumentType` system (see that module's README for the full rationale) drives all of it. Concretely:

- `components/CustomFieldSection.vue` (used for both Personal details and Skills, just a different `category` prop) fetches that category's field definitions plus the worker's existing answers, renders `CustomFieldForm.vue` (one input per field, widget chosen from `field_type`: text/number/date/select/boolean), and saves via a full-replace `POST` on save.
- `components/DocumentUploadSection.vue` (used for both Personal documents and My uploads, just a different `category` prop — `"personal"` or `"work"`) merges the fixed baseline types with any active company-added ones (`api/documentTypes.js`), lets the worker upload against any of them, and lists their own documents already uploaded under that category.
- `components/ContractsSection.vue` lists a worker's full contract history, lets them sign anything `pending_signature`, and downloads the attached file as a blob (the endpoint needs the Bearer token, so a plain link won't work — see `api/contracts.js`).

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
  api/documents.js        fetchMyDocuments()/uploadDocument() — wraps the Employee module's document endpoints
  api/documentTypes.js     fetchDocumentTypes(category) — merges the fixed baseline with active company-added types
  api/customFields.js      fetchCustomFields(category)/fetchAnswers()/saveAnswers() — the company-configurable questions system
  api/passwordReset.js     requestPasswordReset()/resetPassword() — built on raw axios calls like api/invitations.js, since there's no session at this point either
  api/contracts.js         fetchContracts()/signContract()/downloadContract() (blob download, since the endpoint needs the auth token)
  stores/auth.js          Pinia store: companyCode, token, user, login()/logout()/setSession(), persisted to localStorage
  router/index.js         route guard: redirects to /login when unauthenticated (accept-invite and login are public)
  components/layout/AppShell.vue  bottom tab bar wrapping every authenticated page
  components/AccordionItem.vue    reusable expand/collapse item — Profile's sections and their nested sub-sections are all built from this
  api/worker.js             fetchWorker()/updateWorker() — the fixed baseline personal/address/bank fields (partial updates)
  api/shifts.js             fetchShifts()/expressInterest()/withdrawInterest()/fetchMyInterests()/fetchMyAssignments()/confirmAssignment()/requestCancellation() — everything the Jobs tab needs
  api/authProfile.js        updateMe() — self-service name/phone edit (these live on User/Authentication, not Worker/Employee)
  constants/countries.js    country list for the Nationality/Country dropdowns
  constants/languages.js    language list for the Native language dropdown
  components/CustomFieldForm.vue    renders one input per field definition, widget chosen from field_type
  components/MyShiftDetailSheet.vue   shared full-detail bottom sheet for one "my shift" item (an interest or assignment) — everything ShiftResource carries, the change_note banner, and the withdraw/confirm/request-cancellation actions, all self-contained (calls the API itself, emits close/updated). Used by both JobsView and CalendarView so this doesn't get built twice
  components/PersonalDetailsForm.vue  fixed personal fields (name, DOB, gender, marital status, nationality, languages, etc.), plus a self-reported "Citizenship & work authorization" section — a yes/no ("EU/EEA/Swiss/Austrian passport?") that sets a canonical no-visa-needed marker with a passport/ID expiry date, or reveals type+expiry fields for a claimed visa/permit (BOTH paths ask for an expiry date — an EU citizen's passport/ID still expires even though no visa is needed). Shows the admin-confirmed work_authorization_status read-only; never submits it — see the Employee module's README for why (an admin still has to verify against the uploaded document)
  components/AddressForm.vue          fixed address fields (street/house number split, postal code, city, country, residence type)
  components/BankDetailsForm.vue      fixed bank fields — surfaces the backend's account-holder-name-must-match-you error
  components/CustomFieldSection.vue  fetch+save wrapper around CustomFieldForm for one category (personal_info or skill)
  components/DocumentUploadSection.vue  upload form + existing-documents list for one document category (personal or work)
  components/ContractsSection.vue    contract history, sign, and file download
  views/AcceptInviteView.vue   the invite-completion screen — just a password now, not name/phone too (see PersonalDetailsForm.vue, which sets those for real later and is what actually replaces the account's placeholder name)
  views/LoginView.vue          returning-worker sign in — now with a "Forgot your password?" link
  views/ForgotPasswordView.vue   company code + email → sends a reset link (self-service, works even if the worker is currently inactive — see the Authentication module's README)
  views/ResetPasswordView.vue    the link that email opens — token/email/company all read from the URL query, sets a new password
  views/HomeView.vue           the Home tab
  views/JobsView.vue           the Jobs tab — My shifts (interests + assignments merged, sorted by date, detail sheet via the shared MyShiftDetailSheet component) and Available shifts (browsable, filtered client-side to exclude anything already committed to — its own smaller detail overlay, express-interest only, since that action doesn't belong on the shared "my shift" sheet). An assignment with a change_note (a dispatcher edited the time/location after this worker already confirmed) gets a highlighted amber border on its card, surfaced both there and in the shared detail sheet
  views/CalendarView.vue       the Calendar tab — a month grid (Mon-first, prev/next navigation) of every "my shift" (interests + assignments merged, same as Jobs), each day dotted green/amber for confirmed/pending; an All/Confirmed/Pending filter above it; tapping a dotted day opens a small panel listing that day's shift(s) below the grid, tapping one of those opens the full MyShiftDetailSheet
  views/ChatView.vue           the Chat tab — a conversation list (title falls back to the other participant's name for a direct thread), tap one to open a full-screen thread view (bubbles right-aligned for the worker's own messages) with a composer at the bottom. No "start new conversation" here — a worker's threads come from an admin's broadcast or a direct message sent to them; see the Chat module's README for why (the Users listing a "start chat with X" picker would need is admin-only)
  api/chats.js                  fetchConversations()/fetchMessages()/sendMessage()/startDirectConversation() — thin wrapper around the Chat module's endpoints
  views/ProfileView.vue        the Profile tab — ID card + accordion of sections
  assets/main.css               design tokens — same palette/type as admin-panel, for brand consistency
```

## Design note

Single-column, centered-card layouts for auth screens (not the admin panel's split-screen login), and a bottom tab bar rather than a sidebar once logged in — a worker is far more likely to open this on a phone than a desktop.
