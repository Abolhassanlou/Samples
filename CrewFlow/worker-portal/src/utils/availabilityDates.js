/**
 * Date helpers for the weekly availability editor. Everything works on
 * LOCAL calendar dates built from year/month/day parts — never
 * `new Date('2026-10-05')`, which parses as UTC midnight and shows up as
 * the previous day in any timezone behind UTC.
 *
 * repeatEndDate() mirrors the Employee module's AvailabilityRepeat::
 * endDate() (PHP) exactly — the editor's "Sets availability from … to …"
 * line has to match what the server will actually write. Keep the two in
 * step.
 */

export const MAX_SPAN_DAYS = 366

export const pad2 = (n) => String(n).padStart(2, '0')

export function parseYmd(ymd) {
  const [y, m, d] = ymd.split('-').map(Number)
  return new Date(y, m - 1, d)
}

export function formatYmd(date) {
  return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`
}

export function addDays(date, days) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days)
}

/** The Monday of the week containing `date` (local midnight). */
export function mondayOf(date) {
  const daysSinceMonday = (date.getDay() + 6) % 7
  return addDays(date, -daysSinceMonday)
}

/**
 * Calendar-month arithmetic without day-of-month overflow: Jan 31 + 1
 * month is the last day of February, not early March (what plain
 * setMonth() would give). Same as Carbon's addMonthsNoOverflow().
 */
export function addMonthsNoOverflow(date, months) {
  const totalMonths = date.getFullYear() * 12 + date.getMonth() + months
  const year = Math.floor(totalMonths / 12)
  const month = totalMonths % 12
  const lastDayOfTarget = new Date(year, month + 1, 0).getDate()
  return new Date(year, month, Math.min(date.getDate(), lastDayOfTarget))
}

/**
 * The last date (inclusive) a save with this `repeat` covers, starting
 * from `weekStart` (a Monday). `repeat` is { mode: 'none' } |
 * { mode: 'weeks', count } | { mode: 'months', count } |
 * { mode: 'until', until: 'YYYY-MM-DD' }.
 */
export function repeatEndDate(weekStart, repeat) {
  switch (repeat.mode) {
    case 'weeks':
      return addDays(weekStart, repeat.count * 7 - 1)
    case 'months':
      return addDays(addMonthsNoOverflow(weekStart, repeat.count), -1)
    case 'until':
      return parseYmd(repeat.until)
    default:
      return addDays(weekStart, 6)
  }
}
