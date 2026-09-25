<script setup>
import { ref, onMounted, computed } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import MyShiftDetailSheet from '@/components/MyShiftDetailSheet.vue'
import {
  fetchShifts,
  fetchMyInterests,
  fetchMyAssignments,
  expressInterest,
} from '@/api/shifts'

const shifts = ref([])
const myInterests = ref([])
const myAssignments = ref([])
const loading = ref(true)
const actionError = ref('')

// Which shift's position-picker is currently open (for shifts with
// positions, the worker has to say which role before expressing
// interest — see the field-grid of buttons rendered per position).
const busyKey = ref(null)

// Only ever holds a "my shift" item (kind: interest|assignment) — the
// shared MyShiftDetailSheet handles withdraw/confirm/cancel itself.
const selectedMyShift = ref(null)

// A SEPARATE overlay, specific to Jobs — browsing an available shift
// (kind: available) has its own express-interest actions the shared
// sheet doesn't need to know about.
const selectedAvailableShift = ref(null)

async function loadAll() {
  loading.value = true
  actionError.value = ''
  try {
    const [s, interests, assignments] = await Promise.all([
      fetchShifts(),
      fetchMyInterests(),
      fetchMyAssignments(),
    ])
    shifts.value = s
    myInterests.value = interests
    myAssignments.value = assignments
  } catch (error) {
    actionError.value = error.response?.data?.message || 'Could not load jobs. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

// A shift the worker already has an interest or assignment on (for any
// position, or the shift as a whole) shouldn't also show up as
// "available" — it moves to "My shifts" instead.
const committedShiftIds = computed(() => {
  const ids = new Set()
  for (const i of myInterests.value) ids.add(i.shift_id)
  for (const a of myAssignments.value) ids.add(a.shift_id)
  return ids
})

const availableShifts = computed(() => shifts.value.filter((s) => !committedShiftIds.value.has(s.id)))

// "My shifts" — interests and assignments merged into one list, most
// recent first, each tagged with its own kind so the template knows
// which actions/status labels apply.
const myShifts = computed(() => {
  const fromInterests = myInterests.value.map((i) => ({ kind: 'interest', ...i }))
  const fromAssignments = myAssignments.value.map((a) => ({ kind: 'assignment', ...a }))
  return [...fromAssignments, ...fromInterests].sort((a, b) => {
    const aDate = a.shift?.starts_at || ''
    const bDate = b.shift?.starts_at || ''
    return aDate.localeCompare(bDate)
  })
})

function formatDateRange(shift) {
  if (!shift?.starts_at) return ''
  const start = new Date(shift.starts_at)
  const end = shift.ends_at ? new Date(shift.ends_at) : null
  const dateFmt = { month: 'short', day: 'numeric' }
  const timeFmt = { hour: '2-digit', minute: '2-digit' }
  const datePart = start.toLocaleDateString(undefined, dateFmt)
  const startTime = start.toLocaleTimeString(undefined, timeFmt)
  const endTime = end ? end.toLocaleTimeString(undefined, timeFmt) : ''
  return endTime ? `${datePart}, ${startTime} – ${endTime}` : `${datePart}, ${startTime}`
}

function rateLabel(shift) {
  if (shift.rate_type === 'hourly' && shift.hourly_rate) return `€${shift.hourly_rate}/hr`
  if (shift.rate_type === 'fixed' && shift.fixed_amount) return `€${shift.fixed_amount} fixed`
  return ''
}

function statusLabel(item) {
  if (item.kind === 'assignment') {
    return item.status === 'confirmed' ? 'Confirmed' : 'Awaiting your confirmation'
  }
  return item.status === 'waitlisted' ? 'Waitlisted' : 'Interest pending'
}

function statusClass(item) {
  if (item.kind === 'assignment') {
    return item.status === 'confirmed' ? 'status-badge--confirmed' : 'status-badge--pending'
  }
  return item.status === 'waitlisted' ? 'status-badge--waitlisted' : 'status-badge--pending'
}

async function handleExpressInterest(shift, positionId = null) {
  const key = `${shift.id}-${positionId ?? 'none'}`
  busyKey.value = key
  actionError.value = ''
  try {
    await expressInterest(shift.id, positionId)
    await loadAll()
    selectedAvailableShift.value = null
  } catch (error) {
    actionError.value = error.response?.data?.message || 'Could not express interest in this shift.'
  } finally {
    busyKey.value = null
  }
}
</script>

<template>
  <AppShell>
    <header class="header">
      <h1 class="title">Jobs</h1>
    </header>

    <p v-if="loading" class="loading-note">Loading…</p>
    <p v-else-if="actionError" class="error-banner" role="alert">{{ actionError }}</p>

    <template v-else>
      <section class="section">
        <h2 class="section-title">My shifts</h2>
        <div v-if="myShifts.length > 0" class="shift-list">
          <button
            v-for="item in myShifts"
            :key="`${item.kind}-${item.id}`"
            class="shift-card"
            :class="{ 'shift-card--changed': item.change_note }"
            @click="selectedMyShift = item"
          >
            <div class="shift-card-top">
              <span class="shift-title">{{ item.shift?.title }}</span>
              <span class="status-badge" :class="statusClass(item)">{{ statusLabel(item) }}</span>
            </div>
            <p v-if="item.change_note" class="change-note">⚠ Updated — {{ item.change_note }}</p>
            <p class="shift-meta">{{ formatDateRange(item.shift) }}</p>
            <p v-if="item.shift?.location_address" class="shift-meta">{{ item.shift.location_address }}</p>
            <p v-if="item.role_name" class="shift-meta">Role: {{ item.role_name }}</p>
            <p v-if="item.qualification_warning" class="qualification-warning">
              Note: you may not fully meet this shift's usual requirements.
            </p>
            <span class="tap-hint">Tap for full details</span>
          </button>
        </div>
        <p v-else class="empty-note">Nothing yet — express interest in a shift below.</p>
      </section>

      <section class="section">
        <h2 class="section-title">Available shifts</h2>
        <div v-if="availableShifts.length > 0" class="shift-list">
          <div v-for="shift in availableShifts" :key="shift.id" class="shift-card shift-card--available">
            <button class="shift-card-tap" @click="selectedAvailableShift = shift">
              <div class="shift-card-top">
                <span class="shift-title">{{ shift.title }}</span>
                <span v-if="rateLabel(shift)" class="rate-badge">{{ rateLabel(shift) }}</span>
              </div>
              <p class="shift-meta">{{ formatDateRange(shift) }}</p>
              <p v-if="shift.location_address" class="shift-meta">{{ shift.location_address }}</p>
              <span class="tap-hint">Tap for full details</span>
            </button>

            <!-- Shift with specific roles: one interest button per position -->
            <div v-if="shift.positions?.length > 0" class="position-list">
              <div v-for="position in shift.positions" :key="position.id" class="position-row">
                <span class="position-name">
                  {{ position.role_name }}
                  <span class="position-count">({{ position.confirmed_count }}/{{ position.quantity_needed }})</span>
                </span>
                <button
                  class="interest-button"
                  :disabled="busyKey === `${shift.id}-${position.id}`"
                  @click="handleExpressInterest(shift, position.id)"
                >
                  {{ busyKey === `${shift.id}-${position.id}` ? 'Applying…' : 'Express interest' }}
                </button>
              </div>
            </div>

            <!-- Plain shift, no specific roles -->
            <div v-else class="shift-actions">
              <span class="position-count">{{ shift.confirmed_count }}/{{ shift.quantity_needed }} filled</span>
              <button
                class="interest-button"
                :disabled="busyKey === `${shift.id}-none`"
                @click="handleExpressInterest(shift)"
              >
                {{ busyKey === `${shift.id}-none` ? 'Applying…' : 'Express interest' }}
              </button>
            </div>
          </div>
        </div>
        <p v-else class="empty-note">No shifts available right now — check back later.</p>
      </section>
    </template>

    <MyShiftDetailSheet
      :item="selectedMyShift"
      @close="selectedMyShift = null"
      @updated="loadAll"
    />

    <!-- Available-shift detail — its own, smaller overlay (express
         interest only, no withdraw/confirm/cancel). -->
    <div v-if="selectedAvailableShift" class="detail-overlay" @click.self="selectedAvailableShift = null">
      <div class="detail-sheet">
        <div class="detail-header">
          <h2 class="detail-title">{{ selectedAvailableShift.title }}</h2>
          <button class="detail-close" @click="selectedAvailableShift = null">✕</button>
        </div>

        <dl class="detail-list">
          <div class="detail-row">
            <dt>When</dt>
            <dd>{{ formatDateRange(selectedAvailableShift) }}</dd>
          </div>
          <div v-if="selectedAvailableShift.location_address" class="detail-row">
            <dt>Location</dt>
            <dd>{{ selectedAvailableShift.location_address }}</dd>
          </div>
          <div v-if="rateLabel(selectedAvailableShift)" class="detail-row">
            <dt>Rate</dt>
            <dd>{{ rateLabel(selectedAvailableShift) }}</dd>
          </div>
          <div v-if="selectedAvailableShift.internal_contact_name" class="detail-row">
            <dt>Contact</dt>
            <dd>
              {{ selectedAvailableShift.internal_contact_name }}
              <span v-if="selectedAvailableShift.internal_contact_phone"> · {{ selectedAvailableShift.internal_contact_phone }}</span>
            </dd>
          </div>
          <div v-if="selectedAvailableShift.description" class="detail-row detail-row--wide">
            <dt>Description</dt>
            <dd class="detail-description">{{ selectedAvailableShift.description }}</dd>
          </div>
        </dl>

        <p v-if="actionError" class="error-banner error-banner--in-sheet" role="alert">{{ actionError }}</p>

        <div class="detail-actions">
          <template v-if="selectedAvailableShift.positions?.length > 0">
            <div v-for="position in selectedAvailableShift.positions" :key="position.id" class="position-row">
              <span class="position-name">
                {{ position.role_name }}
                <span class="position-count">({{ position.confirmed_count }}/{{ position.quantity_needed }})</span>
              </span>
              <button
                class="interest-button"
                :disabled="busyKey === `${selectedAvailableShift.id}-${position.id}`"
                @click="handleExpressInterest(selectedAvailableShift, position.id)"
              >
                {{ busyKey === `${selectedAvailableShift.id}-${position.id}` ? 'Applying…' : 'Express interest' }}
              </button>
            </div>
          </template>
          <button
            v-else
            class="interest-button interest-button--full"
            :disabled="busyKey === `${selectedAvailableShift.id}-none`"
            @click="handleExpressInterest(selectedAvailableShift)"
          >
            {{ busyKey === `${selectedAvailableShift.id}-none` ? 'Applying…' : 'Express interest' }}
          </button>
        </div>
      </div>
    </div>
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

.error-banner--in-sheet {
  margin: 0 0 1rem;
}

.section {
  padding: 0.5rem 1.25rem 1.5rem;
}

.section-title {
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 0.75rem;
}

.shift-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.shift-card {
  display: block;
  width: 100%;
  text-align: left;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 12px;
  padding: 0.9rem 1rem;
  font-family: inherit;
  cursor: pointer;
}

.shift-card--available {
  padding: 0;
}

.shift-card-tap {
  display: block;
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  padding: 0.9rem 1rem;
  font-family: inherit;
  cursor: pointer;
}

.shift-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.6rem;
  margin-bottom: 0.35rem;
}

.shift-title {
  font-weight: 700;
  font-size: 0.92rem;
  color: var(--color-ink);
}

.shift-meta {
  font-size: 0.82rem;
  color: var(--color-slate);
  margin: 0.15rem 0;
}

.tap-hint {
  display: block;
  font-size: 0.72rem;
  color: var(--color-amber-dark);
  margin-top: 0.4rem;
  font-weight: 600;
}

.rate-badge {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-green);
  white-space: nowrap;
  flex-shrink: 0;
}

.status-badge {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  background: rgba(74, 90, 106, 0.12);
  color: var(--color-slate);
  white-space: nowrap;
  flex-shrink: 0;
}

.status-badge--confirmed {
  background: rgba(76, 139, 108, 0.15);
  color: var(--color-green);
}

.status-badge--pending {
  background: rgba(224, 151, 58, 0.18);
  color: var(--color-amber-dark);
}

.status-badge--waitlisted {
  background: rgba(74, 90, 106, 0.15);
  color: var(--color-slate);
}

.qualification-warning {
  font-size: 0.76rem;
  color: var(--color-amber-dark);
  margin: 0.4rem 0 0;
}

.shift-card--changed {
  border-color: var(--color-amber);
  border-width: 2px;
}

.change-note {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-amber-dark);
  background: rgba(224, 151, 58, 0.14);
  border-radius: 6px;
  padding: 0.4rem 0.6rem;
  margin: 0 0 0.5rem;
}

.shift-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 0 1rem 0.9rem;
  padding-top: 0.6rem;
  border-top: 1px dashed var(--color-line);
}

.position-list {
  margin: 0 1rem 0.9rem;
  padding-top: 0.6rem;
  border-top: 1px dashed var(--color-line);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.position-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
}

.position-name {
  font-size: 0.85rem;
  font-weight: 600;
}

.position-count {
  font-size: 0.75rem;
  font-weight: 400;
  color: var(--color-slate);
}

.interest-button {
  flex-shrink: 0;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.4rem 0.8rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.interest-button--full {
  width: 100%;
  padding: 0.65rem;
}

.interest-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.empty-note {
  color: var(--color-slate);
  font-style: italic;
  font-size: 0.85rem;
}

/* Available-shift detail overlay (smaller, no shared component) */
.detail-overlay {
  position: fixed;
  inset: 0;
  background: rgba(28, 37, 48, 0.5);
  display: flex;
  align-items: flex-end;
  z-index: 30;
}

.detail-sheet {
  width: 100%;
  max-height: 85vh;
  overflow-y: auto;
  background: var(--color-paper);
  border-radius: 16px 16px 0 0;
  padding: 1.25rem 1.25rem calc(1.5rem + env(safe-area-inset-bottom));
}

.detail-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.6rem;
}

.detail-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.15rem;
  margin: 0;
  color: var(--color-ink);
}

.detail-close {
  flex-shrink: 0;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: none;
  background: rgba(74, 90, 106, 0.12);
  color: var(--color-slate);
  font-size: 0.9rem;
  cursor: pointer;
}

.detail-list {
  margin: 1rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

.detail-row dt {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--color-slate);
  margin: 0 0 0.2rem;
}

.detail-row dd {
  font-size: 0.88rem;
  color: var(--color-ink);
  margin: 0;
}

.detail-description {
  white-space: pre-wrap;
  line-height: 1.5;
}

.detail-actions {
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-line);
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
</style>
