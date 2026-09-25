<script setup>
import { ref, onMounted, computed } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import MyShiftDetailSheet from '@/components/MyShiftDetailSheet.vue'
import { fetchMyInterests, fetchMyAssignments } from '@/api/shifts'

const myInterests = ref([])
const myAssignments = ref([])
const loading = ref(true)
const loadError = ref('')

const filter = ref('all') // 'all' | 'confirmed' | 'pending'
const today = new Date()
const viewYear = ref(today.getFullYear())
const viewMonth = ref(today.getMonth()) // 0-11

const selectedDate = ref(null) // 'YYYY-MM-DD' | null — the day panel open below the grid
const selectedItem = ref(null) // the item open in the full detail sheet

async function loadAll() {
  loading.value = true
  loadError.value = ''
  try {
    const [interests, assignments] = await Promise.all([fetchMyInterests(), fetchMyAssignments()])
    myInterests.value = interests
    myAssignments.value = assignments
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Could not load your shifts. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

// Same merge as the Jobs tab — every interest/assignment tagged with
// its own kind so status labels/actions stay consistent everywhere.
const allMyShifts = computed(() => {
  const fromInterests = myInterests.value.map((i) => ({ kind: 'interest', ...i }))
  const fromAssignments = myAssignments.value.map((a) => ({ kind: 'assignment', ...a }))
  return [...fromAssignments, ...fromInterests]
})

// "Confirmed" = a fully-confirmed assignment. "Pending" = everything
// else still waiting on someone (an interest not yet converted, or an
// assignment awaiting this worker's own confirmation).
const filteredShifts = computed(() => {
  if (filter.value === 'confirmed') {
    return allMyShifts.value.filter((i) => i.kind === 'assignment' && i.status === 'confirmed')
  }
  if (filter.value === 'pending') {
    return allMyShifts.value.filter((i) => !(i.kind === 'assignment' && i.status === 'confirmed'))
  }
  return allMyShifts.value
})

function dateKey(isoString) {
  return isoString?.slice(0, 10)
}

// Keyed by "YYYY-MM-DD" (the shift's start date) → array of items that
// day, so the grid only needs one lookup per cell.
const shiftsByDate = computed(() => {
  const map = {}
  for (const item of filteredShifts.value) {
    const key = dateKey(item.shift?.starts_at)
    if (!key) continue
    if (!map[key]) map[key] = []
    map[key].push(item)
  }
  return map
})

const monthLabel = computed(() =>
  new Date(viewYear.value, viewMonth.value, 1).toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
)

const WEEKDAY_LABELS = (() => {
  // Locale-aware Mon–Sun labels, short form.
  const base = new Date(2024, 0, 1) // a Monday
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(base)
    d.setDate(base.getDate() + i)
    return d.toLocaleDateString(undefined, { weekday: 'short' })
  })
})()

// A 6x7 grid, Monday-first, padded with the trailing days of the
// previous/next month so every visible row is a complete week.
const calendarDays = computed(() => {
  const firstOfMonth = new Date(viewYear.value, viewMonth.value, 1)
  const startOffset = (firstOfMonth.getDay() + 6) % 7 // Mon=0 ... Sun=6
  const gridStart = new Date(viewYear.value, viewMonth.value, 1 - startOffset)

  return Array.from({ length: 42 }, (_, i) => {
    const d = new Date(gridStart)
    d.setDate(gridStart.getDate() + i)
    const pad = (n) => String(n).padStart(2, '0')
    const key = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
    return {
      key,
      dayNumber: d.getDate(),
      inCurrentMonth: d.getMonth() === viewMonth.value,
      isToday: key === todayKey(),
      items: shiftsByDate.value[key] || [],
    }
  })
})

function todayKey() {
  const pad = (n) => String(n).padStart(2, '0')
  return `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`
}

function goToPreviousMonth() {
  if (viewMonth.value === 0) {
    viewMonth.value = 11
    viewYear.value -= 1
  } else {
    viewMonth.value -= 1
  }
  selectedDate.value = null
}

function goToNextMonth() {
  if (viewMonth.value === 11) {
    viewMonth.value = 0
    viewYear.value += 1
  } else {
    viewMonth.value += 1
  }
  selectedDate.value = null
}

function selectDay(day) {
  if (day.items.length === 0) return
  selectedDate.value = selectedDate.value === day.key ? null : day.key
}

const selectedDayItems = computed(() => shiftsByDate.value[selectedDate.value] || [])

function selectedDayLabel() {
  if (!selectedDate.value) return ''
  const [y, m, d] = selectedDate.value.split('-').map(Number)
  return new Date(y, m - 1, d).toLocaleDateString(undefined, {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
  })
}

function formatTimeRange(shift) {
  if (!shift?.starts_at) return ''
  const start = new Date(shift.starts_at)
  const end = shift.ends_at ? new Date(shift.ends_at) : null
  const timeFmt = { hour: '2-digit', minute: '2-digit' }
  const startTime = start.toLocaleTimeString(undefined, timeFmt)
  const endTime = end ? end.toLocaleTimeString(undefined, timeFmt) : ''
  return endTime ? `${startTime} – ${endTime}` : startTime
}

function statusDotClass(item) {
  if (item.kind === 'assignment') {
    return item.status === 'confirmed' ? 'dot--confirmed' : 'dot--pending'
  }
  return 'dot--pending'
}

function statusLabel(item) {
  if (item.kind === 'assignment') {
    return item.status === 'confirmed' ? 'Confirmed' : 'Awaiting your confirmation'
  }
  return item.status === 'waitlisted' ? 'Waitlisted' : 'Interest pending'
}

async function handleUpdated() {
  await loadAll()
}
</script>

<template>
  <AppShell>
    <header class="header">
      <h1 class="title">Calendar</h1>
    </header>

    <p v-if="loading" class="loading-note">Loading…</p>
    <p v-else-if="loadError" class="error-banner" role="alert">{{ loadError }}</p>

    <template v-else>
      <div class="filter-row">
        <button class="filter-pill" :class="{ 'filter-pill--active': filter === 'all' }" @click="filter = 'all'">
          All
        </button>
        <button
          class="filter-pill"
          :class="{ 'filter-pill--active': filter === 'confirmed' }"
          @click="filter = 'confirmed'"
        >
          Confirmed
        </button>
        <button
          class="filter-pill"
          :class="{ 'filter-pill--active': filter === 'pending' }"
          @click="filter = 'pending'"
        >
          Pending
        </button>
      </div>

      <div class="month-nav">
        <button class="nav-arrow" @click="goToPreviousMonth">‹</button>
        <span class="month-label">{{ monthLabel }}</span>
        <button class="nav-arrow" @click="goToNextMonth">›</button>
      </div>

      <div class="weekday-row">
        <span v-for="label in WEEKDAY_LABELS" :key="label" class="weekday-label">{{ label }}</span>
      </div>

      <div class="calendar-grid">
        <button
          v-for="day in calendarDays"
          :key="day.key"
          class="day-cell"
          :class="{
            'day-cell--outside': !day.inCurrentMonth,
            'day-cell--today': day.isToday,
            'day-cell--selected': selectedDate === day.key,
            'day-cell--has-shifts': day.items.length > 0,
          }"
          @click="selectDay(day)"
        >
          <span class="day-number">{{ day.dayNumber }}</span>
          <span v-if="day.items.length > 0" class="day-dots">
            <span
              v-for="(item, idx) in day.items.slice(0, 3)"
              :key="idx"
              class="dot"
              :class="statusDotClass(item)"
            ></span>
          </span>
        </button>
      </div>

      <div v-if="selectedDate" class="day-panel">
        <h3 class="day-panel-title">{{ selectedDayLabel() }}</h3>
        <div class="day-shift-list">
          <button
            v-for="item in selectedDayItems"
            :key="`${item.kind}-${item.id}`"
            class="day-shift-row"
            @click="selectedItem = item"
          >
            <span class="dot" :class="statusDotClass(item)"></span>
            <span class="day-shift-info">
              <span class="day-shift-title">{{ item.shift?.title }}</span>
              <span class="day-shift-time">{{ formatTimeRange(item.shift) }} · {{ statusLabel(item) }}</span>
            </span>
          </button>
        </div>
      </div>

      <p v-else class="empty-note">Tap a day with a dot to see your shift for that day.</p>
    </template>

    <MyShiftDetailSheet :item="selectedItem" @close="selectedItem = null" @updated="handleUpdated" />
  </AppShell>
</template>

<style scoped>
.header {
  padding: 1.25rem 1.25rem 0.5rem;
}

.title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.3rem;
  margin: 0;
  color: var(--color-ink);
}

.loading-note {
  padding: 0 1.25rem;
  color: var(--color-slate);
  font-size: 0.9rem;
}

.error-banner {
  margin: 0 1.25rem;
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
}

.filter-row {
  display: flex;
  gap: 0.5rem;
  padding: 0 1.25rem 0.75rem;
}

.filter-pill {
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.4rem 0.9rem;
  color: var(--color-slate);
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 999px;
  cursor: pointer;
}

.filter-pill--active {
  color: var(--color-ink);
  background: rgba(224, 151, 58, 0.18);
  border-color: var(--color-amber);
}

.month-nav {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.25rem;
  padding: 0.25rem 1.25rem 0.75rem;
}

.month-label {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1rem;
  min-width: 11ch;
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

.weekday-row {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  padding: 0 1.25rem;
  margin-bottom: 0.3rem;
}

.weekday-label {
  text-align: center;
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--color-slate);
  text-transform: uppercase;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.25rem;
  padding: 0 1.25rem;
  margin-bottom: 1rem;
}

.day-cell {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.2rem;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  cursor: default;
  padding: 0;
}

.day-cell--has-shifts {
  cursor: pointer;
}

.day-cell--outside {
  opacity: 0.35;
}

.day-cell--today .day-number {
  color: var(--color-amber-dark);
  font-weight: 800;
}

.day-cell--selected {
  border-color: var(--color-amber);
  border-width: 2px;
  background: rgba(224, 151, 58, 0.08);
}

.day-number {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-ink);
}

.day-dots {
  display: flex;
  gap: 0.15rem;
}

.dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--color-slate);
  flex-shrink: 0;
}

.dot--confirmed {
  background: var(--color-green);
}

.dot--pending {
  background: var(--color-amber);
}

.day-panel {
  padding: 0 1.25rem 1.5rem;
}

.day-panel-title {
  font-family: var(--font-display);
  font-size: 0.95rem;
  font-weight: 700;
  margin: 0 0 0.6rem;
}

.day-shift-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.day-shift-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  text-align: left;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 0.7rem 0.85rem;
  font-family: inherit;
  cursor: pointer;
}

.day-shift-row .dot {
  width: 8px;
  height: 8px;
}

.day-shift-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.day-shift-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-ink);
}

.day-shift-time {
  font-size: 0.76rem;
  color: var(--color-slate);
}

.empty-note {
  padding: 0 1.25rem 1.5rem;
  color: var(--color-slate);
  font-style: italic;
  font-size: 0.85rem;
}
</style>
