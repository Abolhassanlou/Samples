import client from '@/api/client'

/**
 * The dated availability rows in an inclusive date range —
 * { date: 'YYYY-MM-DD', day_of_week (0 = Sunday … 6 = Saturday),
 * start_time, end_time }. What the weekly grid loads for the week on
 * screen.
 */
export function fetchAvailabilityRange(userId, from, to) {
  return client.get(`users/${userId}/availability`, { params: { from, to } }).then((r) => r.data.data)
}

/**
 * Saves one week's pattern and copies it forward per `repeat` —
 * { week_start: 'YYYY-MM-DD' (a Monday), slots: [...], repeat: { mode:
 * 'none' | 'weeks' | 'months' | 'until', count?, until? } }. REPLACES
 * every date in the covered span; an empty `slots` clears it (time off).
 * Self or users.manage.
 */
export function saveAvailabilityWeeks(userId, payload) {
  return client.post(`users/${userId}/availability/weeks`, payload).then((r) => r.data.data)
}

/**
 * The worker's booked hours in an inclusive date range — { date,
 * start_time, end_time, label } segments (label = the shift's title). An
 * hour listed here is already committed to a shift and can't be removed
 * from availability; the grid locks it. Self or users.manage.
 */
export function fetchReservedTimes(userId, from, to) {
  return client.get(`users/${userId}/availability/reserved`, { params: { from, to } }).then((r) => r.data.data)
}

/**
 * The weekly TEMPLATE rows (date: null) — a standing "every Tuesday
 * 18:00–22:00" an admin may have entered when creating the worker. The
 * weekly grid doesn't show these, but they still count as availability to
 * a dispatcher, so Home checks them before nudging "you haven't set any".
 */
export function fetchWeeklyTemplate(userId) {
  return client.get(`users/${userId}/availability`).then((r) => r.data.data)
}
