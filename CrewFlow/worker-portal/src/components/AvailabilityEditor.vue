<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { fetchAvailabilityRange, fetchReservedTimes, saveAvailabilityWeeks } from '@/api/availability'
import AvailabilityRepeatSheet from '@/components/AvailabilityRepeatSheet.vue'
import { addDays, formatYmd, mondayOf, parseYmd } from '@/utils/availabilityDates'

const auth = useAuthStore()

// Monday-first display order. The values are the backend's day_of_week
// — 0 = Sunday … 6 = Saturday — and DAY_ORDER's index is also the day's
// offset from the week's Monday.
const DAY_ORDER = [1, 2, 3, 4, 5, 6, 0]

// ---- Resolution ------------------------------------------------------
// Everything inside this component works in QUARTER-HOURS: a quarter is
// the unit that is on or off, saved or booked. The 1 h / 30 min / 15 min
// switch only changes how many quarters one tappable cell stands for —
// it is a zoom, not a different data model. That's deliberate: if the
// hourly view held hours, a 15:45 start (set at 15-minute zoom, or
// entered by an admin) would silently lose its first 15 minutes the next
// time anyone saved from the hourly view. Minutes that aren't a multiple
// of 15 (an admin can type 18:10) round INWARD to the nearest quarter
// on load — under-promising availability rather than over-promising it.
const QUARTER = 15
const QUARTERS_PER_DAY = (24 * 60) / QUARTER // 96
const GRANULARITIES = [60, 30, 15] // minutes one displayed row stands for
const DEFAULT_GRANULARITY = 60
const STORAGE_KEY = 'crewflow.availability.granularity'

// Row height per zoom (px). Finer zoom → shorter rows, so 96 of them
// stay scrollable; the header track is fixed (see .grid in the styles).
const ROW_PX = { 60: 28, 30: 24, 15: 22 }
const ROW_GAP = 2
const strideOf = (g) => ROW_PX[g] + ROW_GAP
const INITIAL_SCROLL_MINUTES = 6 * 60 // open on 06:00, not the dead of night

const DAY_LABELS = (() => {
  const monday = new Date(2024, 0, 1)
  return Object.fromEntries(
    DAY_ORDER.map((dow, i) => {
      const d = new Date(monday)
      d.setDate(monday.getDate() + i)
      return [dow, d.toLocaleDateString(undefined, { weekday: 'short' })]
    })
  )
})()

const pad = (n) => String(n).padStart(2, '0')
const timeLabel = (minutes) => `${pad(Math.floor(minutes / 60))}:${pad(minutes % 60)}`
const quarterKey = (dow, quarter) => `${dow}-${quarter}`

function readStoredGranularity() {
  try {
    const stored = Number(localStorage.getItem(STORAGE_KEY))
    return GRANULARITIES.includes(stored) ? stored : DEFAULT_GRANULARITY
  } catch {
    return DEFAULT_GRANULARITY
  }
}

const granularity = ref(readStoredGranularity())
const stepQuarters = computed(() => granularity.value / QUARTER) // quarters per cell

// ---- Which week is on screen ---------------------------------------
const thisWeekStart = mondayOf(new Date())
const weekStart = ref(thisWeekStart)
const weekEnd = computed(() => addDays(weekStart.value, 6))
// Past weeks can't be edited (the server refuses them too).
const canGoBack = computed(() => weekStart.value.getTime() > thisWeekStart.getTime())

const weekLabel = computed(() => {
  const f = { day: 'numeric', month: 'short' }
  return `${weekStart.value.toLocaleDateString(undefined, f)} – ${weekEnd.value.toLocaleDateString(undefined, { ...f, year: 'numeric' })}`
})

const todayYmd = formatYmd(new Date())
const headDates = computed(() => DAY_ORDER.map((_, i) => addDays(weekStart.value, i)))

// ---- The grid -------------------------------------------------------
// `saved` is what the server has for the week on screen, `draft` is
// what's on screen — the difference between them is what the colors
// show. Both are sets of quarter keys ("dow-quarter", quarter 0–95).
const saved = ref(new Set())
const draft = ref(new Set())
// Quarters a shift is already booked into: quarterKey → the shift's
// title. Locked — they can't be switched off (see reservedCells below).
const locked = ref(new Map())

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const saveError = ref('')
const savedNote = ref('')
const repeatOpen = ref(false)
const gridScroll = ref(null)

const END_OF_DAY = 24 * 60

function toMinutes(time) {
  const [h, m] = time.slice(0, 5).split(':').map(Number)
  const minutes = h * 60 + m
  // 23:59 is how "until midnight" is stored — a time column can't hold
  // 24:00 (see WorkerAvailabilityRequest).
  return minutes === 23 * 60 + 59 ? END_OF_DAY : minutes
}

// A quarter counts as "on" only when a stored slot covers all of it.
function cellsFromSlots(slots) {
  const cells = new Set()
  for (const slot of slots) {
    const start = toMinutes(slot.start_time)
    const end = toMinutes(slot.end_time)
    for (let q = 0; q < QUARTERS_PER_DAY; q++) {
      if (q * QUARTER >= start && (q + 1) * QUARTER <= end) cells.add(quarterKey(slot.day_of_week, q))
    }
  }
  return cells
}

// Consecutive "on" quarters in a day merge into one range.
function slotsFromCells(cells) {
  const slots = []
  for (const dow of DAY_ORDER) {
    let runStart = null
    for (let q = 0; q <= QUARTERS_PER_DAY; q++) {
      const on = q < QUARTERS_PER_DAY && cells.has(quarterKey(dow, q))
      if (on && runStart === null) runStart = q
      if (!on && runStart !== null) {
        const endMinutes = q * QUARTER
        slots.push({
          day_of_week: dow,
          start_time: timeLabel(runStart * QUARTER),
          end_time: endMinutes >= END_OF_DAY ? '23:59' : timeLabel(endMinutes),
        })
        runStart = null
      }
    }
  }
  return slots
}

// A quarter is reserved if ANY part of it overlaps a booked stretch.
// Only the first label is kept when two bookings touch the same quarter.
function reservedCells(segments) {
  const cells = new Map()
  for (const seg of segments) {
    const dow = parseYmd(seg.date).getDay()
    const start = toMinutes(seg.start_time)
    const end = toMinutes(seg.end_time)
    for (let q = 0; q < QUARTERS_PER_DAY; q++) {
      const key = quarterKey(dow, q)
      if (start < (q + 1) * QUARTER && end > q * QUARTER && !cells.has(key)) cells.set(key, seg.label)
    }
  }
  return cells
}

// Navigating quickly fires overlapping requests; only the latest one
// may touch the grid, or a slow earlier week could land on top of it.
let loadSeq = 0
let firstLoad = true

function scrollToMinutes(minutes) {
  if (gridScroll.value) gridScroll.value.scrollTop = (minutes / granularity.value) * strideOf(granularity.value)
}

async function load() {
  const seq = ++loadSeq
  loading.value = true
  loadError.value = ''
  try {
    const from = formatYmd(weekStart.value)
    const to = formatYmd(weekEnd.value)
    const [rows, reserved] = await Promise.all([
      fetchAvailabilityRange(auth.user.id, from, to),
      fetchReservedTimes(auth.user.id, from, to),
    ])
    if (seq !== loadSeq) return
    locked.value = reservedCells(reserved)
    // A booked quarter is always "on", and counts as already saved: it
    // may not have an availability row yet (a dispatcher can assign
    // someone without one), and the next save writes it — but it must
    // never make the grid look edited when the worker hasn't touched
    // anything.
    saved.value = new Set([...cellsFromSlots(rows), ...locked.value.keys()])
    draft.value = new Set(saved.value)
  } catch (error) {
    if (seq !== loadSeq) return
    loadError.value = error.response?.data?.message || 'Could not load your availability. Check your connection and try again.'
  } finally {
    if (seq === loadSeq) loading.value = false
  }
  if (seq !== loadSeq) return
  if (firstLoad) {
    firstLoad = false
    await nextTick()
    scrollToMinutes(INITIAL_SCROLL_MINUTES)
  }
}

onMounted(load)

const dirty = computed(() => {
  if (saved.value.size !== draft.value.size) return true
  for (const k of draft.value) if (!saved.value.has(k)) return true
  return false
})

async function changeWeek(deltaWeeks) {
  if (deltaWeeks < 0 && !canGoBack.value) return
  if (dirty.value && !window.confirm('Discard your unsaved changes for this week?')) return
  weekStart.value = addDays(weekStart.value, deltaWeeks * 7)
  savedNote.value = ''
  saveError.value = ''
  await load()
}

// Zoom: same data, different cell size. Keeps whatever time is at the
// top of the visible area at the top, rather than jumping back to 06:00.
async function setGranularity(next) {
  if (next === granularity.value) return
  const el = gridScroll.value
  const topMinutes = el ? (el.scrollTop / strideOf(granularity.value)) * granularity.value : INITIAL_SCROLL_MINUTES
  granularity.value = next
  try {
    localStorage.setItem(STORAGE_KEY, String(next))
  } catch {
    // Remembering the choice is a convenience, not a requirement.
  }
  await nextTick()
  scrollToMinutes(topMinutes)
}

// ---- Drawing a cell ---------------------------------------------------
const STATE_COLORS = {
  off: '#fff',
  saved: 'var(--color-green)',
  added: 'var(--color-amber)',
  removed: 'rgba(181, 83, 63, 0.16)',
  locked: 'var(--color-green)',
}

function quarterState(dow, quarter) {
  const key = quarterKey(dow, quarter)
  if (locked.value.has(key)) return 'locked'
  const wasSaved = saved.value.has(key)
  const isOn = draft.value.has(key)
  if (wasSaved && isOn) return 'saved'
  if (isOn) return 'added'
  if (wasSaved) return 'removed'
  return 'off'
}

const cellQuarters = (row) => Array.from({ length: stepQuarters.value }, (_, i) => row * stepQuarters.value + i)

// One cell covers `stepQuarters` quarters. If they all share a state it
// draws as that state; otherwise it's a vertical stack of the quarters'
// own colors (earlier at the top) — so an hour that only starts at 15:45
// shows its bottom quarter filled, not a rounded-up full hour.
function cellView(dow, row) {
  const quarters = cellQuarters(row)
  const states = quarters.map((q) => quarterState(dow, q))
  const allSame = states.every((s) => s === states[0])
  const lockedQuarter = quarters.find((q) => locked.value.has(quarterKey(dow, q)))
  const title =
    lockedQuarter !== undefined ? `Booked: ${locked.value.get(quarterKey(dow, lockedQuarter))}` : undefined
  const on = states.every((s) => s === 'saved' || s === 'added' || s === 'locked')

  if (allSame) {
    return { cls: `cell--${states[0]}`, style: null, on, allLocked: states[0] === 'locked', hasLock: states[0] === 'locked', title }
  }
  const n = states.length
  const stops = states.map((s, i) => `${STATE_COLORS[s]} ${(i * 100) / n}% ${((i + 1) * 100) / n}%`).join(', ')
  return {
    cls: 'cell--mixed',
    style: { background: `linear-gradient(to bottom, ${stops})` },
    on,
    allLocked: false,
    hasLock: lockedQuarter !== undefined,
    title,
  }
}

const viewRows = computed(() => {
  const rowCount = QUARTERS_PER_DAY / stepQuarters.value
  return Array.from({ length: rowCount }, (_, row) => {
    const minutes = row * granularity.value
    return { row, minutes, label: timeLabel(minutes), cells: DAY_ORDER.map((dow) => cellView(dow, row)) }
  })
})

// ---- Editing ----------------------------------------------------------
// One rule for a cell, a whole day and a whole row alike: booked
// quarters sit out entirely (they neither count toward "is the whole
// line on" nor get switched); of the rest, if every one is already on
// the line switches off, otherwise it fills. So tapping a half-filled
// cell completes it, and "Mon–Fri evenings" is a few taps.
function toggleLine(allKeys) {
  const keys = allKeys.filter((k) => !locked.value.has(k))
  if (keys.length === 0) return
  const next = new Set(draft.value)
  const allOn = keys.every((k) => next.has(k))
  for (const k of keys) {
    if (allOn) next.delete(k)
    else next.add(k)
  }
  draft.value = next
  savedNote.value = ''
}

const toggleCell = (dow, row) => toggleLine(cellQuarters(row).map((q) => quarterKey(dow, q)))
const toggleDay = (dow) => toggleLine(Array.from({ length: QUARTERS_PER_DAY }, (_, q) => quarterKey(dow, q)))
const toggleRow = (row) => toggleLine(DAY_ORDER.flatMap((dow) => cellQuarters(row).map((q) => quarterKey(dow, q))))

function discard() {
  draft.value = new Set(saved.value)
  saveError.value = ''
}

// Quarters → "12", "12.5", "12.75".
const hoursThisWeek = computed(() => draft.value.size / 4)

// ---- Saving: always asks whether to repeat -------------------------
function openRepeat() {
  saveError.value = ''
  repeatOpen.value = true
}

async function handleConfirmRepeat(repeat) {
  saving.value = true
  saveError.value = ''
  try {
    const result = await saveAvailabilityWeeks(auth.user.id, {
      week_start: formatYmd(weekStart.value),
      slots: slotsFromCells(draft.value),
      repeat,
    })
    repeatOpen.value = false
    const through = parseYmd(result.to).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
    savedNote.value =
      `Availability saved through ${through}.` +
      (result.reserved_kept > 0
        ? ` ${result.reserved_kept} booked slot${result.reserved_kept === 1 ? '' : 's'} in that period stayed available because a shift is booked there.`
        : '')
    // Reload from the server rather than trusting our own draft — that's
    // the real "saved" state for this week now.
    await load()
    setTimeout(() => (savedNote.value = ''), 4000)
  } catch (error) {
    const fieldErrors = error.response?.data?.errors
    saveError.value = fieldErrors
      ? Object.values(fieldErrors).flat().join(' ')
      : error.response?.data?.message || 'Could not save your availability. Please try again.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="availability" :style="{ '--row-height': `${ROW_PX[granularity]}px` }">
    <p class="lead">
      Pick a week and tap the times you can work, then Save — you'll be asked whether to repeat it for
      more weeks. Starting at a quarter past or quarter to? Zoom in to 30 or 15 minutes. Tap a day or a
      time label to switch a whole column or row at once.
    </p>

    <div class="week-nav">
      <button class="nav-arrow" :disabled="!canGoBack" aria-label="Previous week" @click="changeWeek(-1)">‹</button>
      <span class="week-label">{{ weekLabel }}</span>
      <button class="nav-arrow" aria-label="Next week" @click="changeWeek(1)">›</button>
    </div>

    <div class="zoom-switch" role="group" aria-label="Grid resolution">
      <button
        v-for="g in GRANULARITIES"
        :key="g"
        class="zoom-tab"
        :class="{ 'zoom-tab--active': granularity === g }"
        :aria-pressed="granularity === g"
        @click="setGranularity(g)"
      >
        {{ g === 60 ? '1 h' : `${g} min` }}
      </button>
    </div>

    <p v-if="loading" class="loading-note">Loading…</p>
    <p v-else-if="loadError" class="error-banner" role="alert">{{ loadError }}</p>

    <template v-else>
      <div ref="gridScroll" class="grid-scroll">
        <div class="grid">
          <div class="corner"></div>
          <button
            v-for="(dow, i) in DAY_ORDER"
            :key="`head-${dow}`"
            class="day-head"
            :class="{ 'day-head--today': formatYmd(headDates[i]) === todayYmd }"
            @click="toggleDay(dow)"
          >
            <span>{{ DAY_LABELS[dow] }}</span>
            <span class="day-head-date">{{ headDates[i].getDate() }}</span>
          </button>

          <template v-for="r in viewRows" :key="r.row">
            <button class="hour-label" :class="{ 'hour-label--minor': r.minutes % 60 !== 0 }" @click="toggleRow(r.row)">
              {{ r.label }}
            </button>
            <button
              v-for="(c, i) in r.cells"
              :key="`${r.row}-${i}`"
              class="cell"
              :class="[c.cls, { 'cell--hour-start': r.minutes % 60 === 0, 'cell--has-lock': c.hasLock }]"
              :style="c.style"
              :aria-pressed="c.on"
              :aria-disabled="c.allLocked"
              :aria-label="`${DAY_LABELS[DAY_ORDER[i]]} ${r.label}`"
              :title="c.title"
              @click="toggleCell(DAY_ORDER[i], r.row)"
            ></button>
          </template>
        </div>
      </div>

      <ul class="legend">
        <li><span class="swatch swatch--saved"></span>Saved</li>
        <li><span class="swatch swatch--added"></span>Added — not saved</li>
        <li><span class="swatch swatch--removed"></span>Removed — not saved</li>
        <li><span class="swatch swatch--locked"></span>Booked for a shift — can't be removed</li>
      </ul>
      <p v-if="granularity > QUARTER" class="zoom-hint">
        A partly filled cell means only part of that period is available — zoom in to see exactly when.
      </p>

      <p class="summary">{{ hoursThisWeek }} hour{{ hoursThisWeek === 1 ? '' : 's' }} this week</p>

      <p v-if="saveError && !repeatOpen" class="error-banner error-banner--inline" role="alert">{{ saveError }}</p>
      <p v-if="savedNote" class="saved-note">{{ savedNote }}</p>

      <div class="actions">
        <button v-if="dirty" class="save-button" :disabled="saving" @click="openRepeat">Save changes</button>
        <!-- Nothing changed, but this week has availability: let the worker
             carry it forward without having to wiggle a cell first. -->
        <button v-else-if="draft.size > 0" class="repeat-button" @click="openRepeat">Repeat this week…</button>
        <button v-if="dirty && !saving" class="discard-button" @click="discard">Discard</button>
      </div>
    </template>

    <AvailabilityRepeatSheet
      :open="repeatOpen"
      :week-start="weekStart"
      :saving="saving"
      :error="saveError"
      @cancel="repeatOpen = false"
      @confirm="handleConfirmRepeat"
    />
  </div>
</template>

<style scoped>
.availability {
  --row-height: 28px;
  padding: 0 1.25rem 1.5rem;
}

.lead {
  font-size: 0.82rem;
  color: var(--color-slate);
  line-height: 1.5;
  margin: 0 0 0.9rem;
}

.week-nav {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.week-label {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 0.95rem;
  min-width: 13ch;
  text-align: center;
}

.nav-arrow {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid var(--color-line);
  background: #fff;
  font-size: 1.1rem;
  color: var(--color-ink);
  cursor: pointer;
}

.nav-arrow:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.zoom-switch {
  display: flex;
  width: fit-content;
  margin: 0 auto 0.75rem;
  border: 1px solid var(--color-line);
  border-radius: 999px;
  overflow: hidden;
  background: #fff;
}

.zoom-tab {
  padding: 0.35rem 0.9rem;
  border: none;
  background: none;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-slate);
  cursor: pointer;
}

.zoom-tab--active {
  background: var(--color-amber);
  color: var(--color-ink);
}

.loading-note {
  color: var(--color-slate);
  font-size: 0.9rem;
}

.error-banner {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
}

.error-banner--inline {
  margin: 0.75rem 0;
}

/* The grid scrolls inside its own box so the day header can stay
   pinned while the rows move. */
.grid-scroll {
  max-height: 58vh;
  overflow-y: auto;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  background: #fff;
}

/* The header is its own fixed-height track (the first row); every row
   after it follows --row-height, which changes with the zoom. */
.grid {
  display: grid;
  grid-template-columns: 3rem repeat(7, 1fr);
  grid-template-rows: 2.6rem;
  grid-auto-rows: var(--row-height);
  gap: 2px;
  padding: 0 2px 2px;
}

.corner,
.day-head {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #fff;
}

.day-head {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.05rem;
  border: none;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-ink);
  cursor: pointer;
  padding: 0;
}

.day-head-date {
  font-size: 0.68rem;
  font-weight: 600;
  color: var(--color-slate);
}

.day-head--today .day-head-date {
  color: var(--color-amber-dark);
  font-weight: 800;
}

.hour-label {
  border: none;
  background: none;
  padding: 0 0.35rem 0 0;
  font-size: 0.68rem;
  font-weight: 600;
  color: var(--color-slate);
  text-align: right;
  cursor: pointer;
}

/* The :15/:30/:45 rows are secondary to the hour they sit in. */
.hour-label--minor {
  font-size: 0.6rem;
  font-weight: 500;
  opacity: 0.65;
}

.cell {
  position: relative;
  border: 1px solid var(--color-line);
  border-radius: 4px;
  background: #fff;
  padding: 0;
  cursor: pointer;
}

.cell--saved {
  background: var(--color-green);
  border-color: var(--color-green);
}

.cell--added {
  background: var(--color-amber);
  border-color: var(--color-amber);
}

.cell--removed {
  background: rgba(181, 83, 63, 0.16);
  border-color: rgba(181, 83, 63, 0.55);
}

/* A cell whose quarters differ draws them as stacked bands via an inline
   gradient; only the border is styled here. */
.cell--mixed {
  border-color: var(--color-slate);
}

/* Booked for a shift: solid green like "saved", striped so it reads as
   fixed rather than just on. */
.cell--locked,
.swatch--locked {
  background: repeating-linear-gradient(135deg, var(--color-green) 0 4px, rgba(255, 255, 255, 0.4) 4px 8px);
  border-color: var(--color-green);
}

.cell--locked {
  cursor: not-allowed;
}

/* Where a booking sits inside a cell that isn't entirely booked. */
.cell--has-lock::after {
  content: '🔒';
  position: absolute;
  top: 0;
  right: 1px;
  font-size: 0.5rem;
  line-height: 1;
  pointer-events: none;
}

/* A slightly darker top edge at each full hour, so the hour boundaries
   stay readable at the finer zooms. */
.cell--hour-start {
  border-top-color: var(--color-slate);
}

.legend {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem 1rem;
  list-style: none;
  padding: 0;
  margin: 0.75rem 0 0.5rem;
  font-size: 0.74rem;
  color: var(--color-slate);
}

.legend li {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.swatch {
  width: 12px;
  height: 12px;
  border-radius: 3px;
  border: 1px solid var(--color-line);
}

.swatch--saved {
  background: var(--color-green);
  border-color: var(--color-green);
}

.swatch--added {
  background: var(--color-amber);
  border-color: var(--color-amber);
}

.swatch--removed {
  background: rgba(181, 83, 63, 0.16);
  border-color: rgba(181, 83, 63, 0.55);
}

.zoom-hint {
  font-size: 0.74rem;
  color: var(--color-slate);
  margin: 0 0 0.6rem;
  line-height: 1.4;
}

.summary {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-ink);
  margin: 0 0 0.75rem;
}

.saved-note {
  font-size: 0.82rem;
  color: var(--color-green);
  margin: 0 0 0.75rem;
}

.actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.save-button,
.repeat-button {
  padding: 0.65rem 1.25rem;
  font-size: 0.88rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
}

.save-button {
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
}

.repeat-button {
  color: var(--color-ink);
  background: #fff;
  border: 1px solid var(--color-line);
}

.save-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.discard-button {
  background: none;
  border: none;
  padding: 0;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-slate);
  cursor: pointer;
}
</style>
