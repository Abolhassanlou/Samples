/**
 * The decisions behind the Home tab, kept out of the view so they can be
 * tested without a browser. Everything takes `now` as a parameter rather
 * than reading the clock itself.
 *
 * Shapes in: an assignment is { id, shift_id, status, change_note,
 * role_name, shift: { id, title, starts_at, ends_at, status,
 * location_address } } — what GET /my-assignments returns; an interest
 * is the same with status 'pending' | 'waitlisted'.
 */
import { parseYmd } from '@/utils/availabilityDates'
import { EU_CITIZEN_MARKER } from '@/utils/workAuthorization'

/** How far ahead an expiring document starts to be flagged. */
export const EXPIRY_WARNING_DAYS = 30

/** How many of the following confirmed shifts the "Coming up" list shows. */
export const COMING_UP_COUNT = 3

const DAY_MS = 24 * 60 * 60 * 1000
const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate())

// Whole calendar days from `now`'s date to `date`'s date. Rounded, not
// truncated: across a daylight-saving change a day is 23 or 25 hours long.
export const calendarDaysBetween = (now, date) => Math.round((startOfDay(date) - startOfDay(now)) / DAY_MS)

const endOf = (shift) => new Date(shift.ends_at ?? shift.starts_at)

// A shift still worth showing: not cancelled, and not already over. A
// cancelled shift keeps its assignments as 'confirmed' rows, so the
// assignment status alone would keep showing it as someone's next job.
function isLive(shift, now) {
  return !!shift && shift.status !== 'cancelled' && endOf(shift) > now
}

export function isInProgress(shift, now) {
  return new Date(shift.starts_at) <= now && endOf(shift) > now
}

/**
 * Sorts the worker's shifts into what Home shows:
 *  - awaiting:  assigned, but they haven't confirmed yet (this is also
 *               where a shift that was edited after confirmation lands,
 *               carrying its change_note) — soonest first
 *  - next:      the soonest confirmed shift (possibly in progress right now)
 *  - comingUp:  the next few confirmed ones after it
 *  - waitingCount: shifts they've applied to that nobody has decided on
 */
export function organizeMyShifts(assignments, interests, now = new Date()) {
  const bySoonest = (a, b) => new Date(a.shift.starts_at) - new Date(b.shift.starts_at)

  const live = assignments.filter((a) => isLive(a.shift, now))
  const awaiting = live.filter((a) => a.status === 'pending_worker_confirmation').sort(bySoonest)
  const confirmed = live.filter((a) => a.status === 'confirmed').sort(bySoonest)

  // An interest that has already become an assignment must not also
  // count as "still waiting to hear back".
  const assignedShiftIds = new Set(assignments.map((a) => a.shift_id))
  const waitingCount = interests.filter(
    (i) => ['pending', 'waitlisted'].includes(i.status) && !assignedShiftIds.has(i.shift_id) && isLive(i.shift, now)
  ).length

  return {
    awaiting,
    next: confirmed[0] ?? null,
    comingUp: confirmed.slice(1, 1 + COMING_UP_COUNT),
    waitingCount,
  }
}

/** "Today", "Tomorrow", a weekday within the week, otherwise a short date. */
export function relativeDayLabel(date, now = new Date(), locale = undefined) {
  const diff = calendarDaysBetween(now, date)
  if (diff === 0) return 'Today'
  if (diff === 1) return 'Tomorrow'
  if (diff > 1 && diff < 7) return date.toLocaleDateString(locale, { weekday: 'long' })
  return date.toLocaleDateString(locale, { weekday: 'short', day: 'numeric', month: 'short' })
}

/**
 * A notice about the worker's own passport/ID/permit, or null. `expiry`
 * on the worker record is a date ('YYYY-MM-DD…'), read here by its date
 * parts — parsing it as an instant would show the previous day anywhere
 * behind UTC.
 *
 * Levels: 'expired' (the date is before today), 'today' (it is today —
 * deliberately NOT worded as expired: a document is normally good
 * through its last day), 'soon' (within EXPIRY_WARNING_DAYS).
 */
export function expiryNotice(worker, now = new Date()) {
  const raw = worker?.work_authorization_expiry_date
  if (!raw) return null

  const expiry = parseYmd(raw.slice(0, 10))
  const daysLeft = calendarDaysBetween(now, expiry)
  const noun = worker.work_authorization_type === EU_CITIZEN_MARKER ? 'passport/ID' : 'work permit'

  if (daysLeft < 0) return { level: 'expired', daysLeft, expiry, noun }
  if (daysLeft === 0) return { level: 'today', daysLeft, expiry, noun }
  if (daysLeft <= EXPIRY_WARNING_DAYS) return { level: 'soon', daysLeft, expiry, noun }
  return null
}

/**
 * Nudge to set availability — only when we KNOW there is none. A failed
 * request (null) means "don't know", so no nudge: better silent than
 * wrong. A weekly template an admin entered counts as availability.
 */
export function needsAvailabilityNudge(datedRowsNext14Days, weeklyTemplateRows) {
  if (datedRowsNext14Days == null || weeklyTemplateRows == null) return false
  return datedRowsNext14Days.length === 0 && weeklyTemplateRows.length === 0
}

/** "19:00 – 23:00", or "22:00 – 02:00 (+1)" when the shift ends on a later date. */
export function formatTimeRange(shift, locale = undefined) {
  const timeFormat = { hour: '2-digit', minute: '2-digit' }
  const start = new Date(shift.starts_at)
  const startText = start.toLocaleTimeString(locale, timeFormat)
  if (!shift.ends_at) return startText

  const end = new Date(shift.ends_at)
  const endsLater = calendarDaysBetween(start, end) > 0
  return `${startText} – ${end.toLocaleTimeString(locale, timeFormat)}${endsLater ? ' (+1)' : ''}`
}
