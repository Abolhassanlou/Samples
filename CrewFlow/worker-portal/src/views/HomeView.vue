<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppShell from '@/components/layout/AppShell.vue'
import MyShiftDetailSheet from '@/components/MyShiftDetailSheet.vue'
import HomeShiftRow from '@/components/HomeShiftRow.vue'
import { fetchMyAssignments, fetchMyInterests } from '@/api/shifts'
import { fetchWorker } from '@/api/worker'
import { fetchAvailabilityRange, fetchWeeklyTemplate } from '@/api/availability'
import { addDays, formatYmd } from '@/utils/availabilityDates'
import {
  organizeMyShifts,
  relativeDayLabel,
  expiryNotice,
  needsAvailabilityNudge,
  isInProgress,
  formatTimeRange,
} from '@/utils/homeDashboard'

const auth = useAuthStore()

const now = ref(new Date())
const assignments = ref([])
const interests = ref([])
// The three below are secondary: if one of those requests fails the
// dashboard still shows the shifts, and the card that depends on it
// simply doesn't appear (null = "couldn't find out", never "none").
const worker = ref(null)
const datedAvailability = ref(null)
const weeklyTemplate = ref(null)

const loading = ref(true)
const loadError = ref('')
const selectedItem = ref(null) // the assignment open in the shared detail sheet

async function loadAll() {
  loading.value = true
  loadError.value = ''
  now.value = new Date()
  const userId = auth.user.id
  const from = formatYmd(now.value)
  const to = formatYmd(addDays(now.value, 13))

  const [mine, interested, workerRecord, dated, template] = await Promise.allSettled([
    fetchMyAssignments(),
    fetchMyInterests(),
    fetchWorker(userId),
    fetchAvailabilityRange(userId, from, to),
    fetchWeeklyTemplate(userId),
  ])

  // The shifts are the point of this screen; without them say so.
  if (mine.status === 'rejected' || interested.status === 'rejected') {
    const reason = mine.status === 'rejected' ? mine.reason : interested.reason
    loadError.value = reason?.response?.data?.message || 'Could not load your shifts. Check your connection and try again.'
  }
  assignments.value = mine.status === 'fulfilled' ? mine.value : []
  interests.value = interested.status === 'fulfilled' ? interested.value : []

  worker.value = workerRecord.status === 'fulfilled' ? workerRecord.value : null
  datedAvailability.value = dated.status === 'fulfilled' ? dated.value : null
  weeklyTemplate.value = template.status === 'fulfilled' ? template.value : null

  loading.value = false
}

onMounted(loadAll)

const firstName = computed(() => auth.user?.name?.split(' ')[0] || 'there')
const todayLabel = computed(() =>
  now.value.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' })
)

const organized = computed(() => organizeMyShifts(assignments.value, interests.value, now.value))
const nextShift = computed(() => organized.value.next)
const happeningNow = computed(() => !!nextShift.value && isInProgress(nextShift.value.shift, now.value))

const notice = computed(() => expiryNotice(worker.value, now.value))
const noticeText = computed(() => {
  const n = notice.value
  if (!n) return null
  const date = n.expiry.toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' })
  if (n.level === 'expired') {
    return {
      title: `Your ${n.noun} has expired`,
      body: `It ran out on ${date}. You can't be assigned to shifts while it's expired — add the new date in your profile and an admin will confirm it.`,
    }
  }
  if (n.level === 'today') {
    return {
      title: `Your ${n.noun} expires today`,
      body: 'Renew it, then add the new date in your profile so an admin can confirm it and you keep getting shifts.',
    }
  }
  return {
    title: `Your ${n.noun} expires in ${n.daysLeft} day${n.daysLeft === 1 ? '' : 's'}`,
    body: `It runs out on ${date}. Renew it, then add the new date in your profile so an admin can confirm it and you keep getting shifts.`,
  }
})

const availabilityNudge = computed(() => needsAvailabilityNudge(datedAvailability.value, weeklyTemplate.value))

// Everything on this screen opens the same detail sheet the Jobs and
// Calendar tabs use, so confirming/cancelling works identically here.
const open = (assignment) => (selectedItem.value = { kind: 'assignment', ...assignment })

const dayOf = (shift) => relativeDayLabel(new Date(shift.starts_at), now.value)
</script>

<template>
  <AppShell>
    <header class="header">
      <span class="brand-mark">CrewFlow</span>
    </header>

    <div class="body">
      <h1 class="greeting">Hi, {{ firstName }}</h1>
      <p class="today">{{ todayLabel }}</p>

      <p v-if="loading" class="loading-note">Loading…</p>

      <template v-else>
        <p v-if="loadError" class="error-banner" role="alert">
          {{ loadError }}
          <button class="link-button" @click="loadAll">Try again</button>
        </p>

        <!-- Their own document: first, because an expired one stops them being assigned at all. -->
        <section v-if="noticeText" class="notice" :class="notice.level === 'soon' ? 'notice--warn' : 'notice--danger'">
          <h2 class="notice-title">{{ noticeText.title }}</h2>
          <p class="notice-body">{{ noticeText.body }}</p>
          <RouterLink to="/profile" class="notice-link">Open profile</RouterLink>
        </section>

        <section v-if="organized.awaiting.length" class="block">
          <h2 class="block-title">
            Needs your confirmation
            <span class="count">{{ organized.awaiting.length }}</span>
          </h2>
          <div class="stack">
            <HomeShiftRow
              v-for="a in organized.awaiting"
              :key="a.id"
              :item="a"
              :now="now"
              cta="Review"
              @open="open(a)"
            />
          </div>
        </section>

        <section class="block">
          <h2 class="block-title">Next shift</h2>

          <button v-if="nextShift" class="hero" @click="open(nextShift)">
            <span class="hero-day">
              {{ happeningNow ? 'Happening now' : dayOf(nextShift.shift) }}
            </span>
            <span class="hero-title">{{ nextShift.shift.title }}</span>
            <span class="hero-time">{{ formatTimeRange(nextShift.shift) }}</span>
            <span v-if="nextShift.shift.location_address" class="hero-meta">{{ nextShift.shift.location_address }}</span>
            <span v-if="nextShift.role_name" class="hero-meta">{{ nextShift.role_name }}</span>
          </button>

          <div v-else class="empty-card">
            <p class="empty-text">No confirmed shifts coming up.</p>
            <RouterLink to="/jobs" class="empty-link">Browse open shifts</RouterLink>
          </div>
        </section>

        <section v-if="organized.comingUp.length" class="block">
          <h2 class="block-title">Coming up</h2>
          <div class="stack">
            <HomeShiftRow v-for="a in organized.comingUp" :key="a.id" :item="a" :now="now" @open="open(a)" />
          </div>
        </section>

        <p v-if="organized.waitingCount" class="waiting">
          You've shown interest in {{ organized.waitingCount }}
          shift{{ organized.waitingCount === 1 ? '' : 's' }} and are waiting to hear back.
          <RouterLink to="/jobs" class="inline-link">View in Jobs</RouterLink>
        </p>

        <section v-if="availabilityNudge" class="notice notice--info">
          <h2 class="notice-title">Set your availability</h2>
          <p class="notice-body">
            You haven't set when you're free for the next two weeks. Dispatchers use it to find who can work.
          </p>
          <RouterLink :to="{ path: '/calendar', query: { view: 'availability' } }" class="notice-link">
            Set availability
          </RouterLink>
        </section>
      </template>
    </div>

    <MyShiftDetailSheet :item="selectedItem" @close="selectedItem = null" @updated="loadAll" />
  </AppShell>
</template>

<style scoped>
.header {
  padding: 1rem 1.25rem 0.5rem;
}

.brand-mark {
  font-family: var(--font-display);
  font-weight: 800;
  font-size: 1.1rem;
  color: var(--color-ink);
}

.body {
  padding: 0.5rem 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.greeting {
  font-family: var(--font-display);
  font-weight: 700;
  margin: 0;
}

.today {
  margin: -0.85rem 0 0;
  font-size: 0.85rem;
  color: var(--color-slate);
}

.loading-note {
  color: var(--color-slate);
  font-size: 0.9rem;
  margin: 0;
}

.error-banner {
  margin: 0;
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
}

.link-button {
  background: none;
  border: none;
  padding: 0;
  margin-left: 0.4rem;
  font: inherit;
  font-weight: 700;
  color: inherit;
  text-decoration: underline;
  cursor: pointer;
}

.block {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.block-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  font-family: var(--font-display);
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-ink);
}

.count {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-ink);
  background: var(--color-amber);
  border-radius: 999px;
  padding: 0.05rem 0.5rem;
}

.stack {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* The next shift: the one thing on this screen worth a big card. */
.hero {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.15rem;
  width: 100%;
  text-align: left;
  background: #fff;
  border: 1px solid var(--color-line);
  border-left: 4px solid var(--color-amber);
  border-radius: 12px;
  padding: 1rem 1.1rem;
  font-family: inherit;
  cursor: pointer;
}

.hero-day {
  font-size: 0.74rem;
  font-weight: 800;
  color: var(--color-amber-dark);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.hero-title {
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-ink);
}

.hero-time {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-ink);
}

.hero-meta {
  font-size: 0.82rem;
  color: var(--color-slate);
}

.empty-card {
  background: #fff;
  border: 1px dashed var(--color-line);
  border-radius: 12px;
  padding: 1rem 1.1rem;
}

.empty-text {
  margin: 0 0 0.35rem;
  font-size: 0.88rem;
  color: var(--color-slate);
}

.empty-link,
.inline-link {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-amber-dark);
  text-decoration: none;
}

.waiting {
  margin: 0;
  font-size: 0.82rem;
  color: var(--color-slate);
  line-height: 1.5;
}

.inline-link {
  margin-left: 0.25rem;
}

.notice {
  border-radius: 12px;
  padding: 0.9rem 1rem;
  border: 1px solid var(--color-line);
  background: #fff;
}

.notice--danger {
  background: rgba(181, 83, 63, 0.08);
  border-color: rgba(181, 83, 63, 0.35);
}

.notice--warn {
  background: rgba(224, 151, 58, 0.12);
  border-color: rgba(224, 151, 58, 0.4);
}

.notice-title {
  margin: 0 0 0.25rem;
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-ink);
}

.notice-body {
  margin: 0 0 0.5rem;
  font-size: 0.82rem;
  line-height: 1.5;
  color: var(--color-ink);
}

.notice-link {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-amber-dark);
  text-decoration: none;
}
</style>
