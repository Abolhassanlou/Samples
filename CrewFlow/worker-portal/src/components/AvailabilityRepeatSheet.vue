<script setup>
import { ref, computed, watch } from 'vue'
import { addDays, formatYmd, parseYmd, repeatEndDate, MAX_SPAN_DAYS } from '@/utils/availabilityDates'

const props = defineProps({
  open: { type: Boolean, default: false },
  weekStart: { type: Date, required: true }, // the Monday of the week being saved
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['cancel', 'confirm'])

// Defaults mirror what the worker is most likely to want after filling
// in a week: carry it forward a few weeks. The summary line below always
// states exactly which dates this will overwrite, so a default that
// repeats is never a silent surprise.
const repeats = ref(true)
const endKind = ref('weeks') // 'weeks' | 'months' | 'until'
const weeksCount = ref(4)
const monthsCount = ref(4)
const untilDate = ref('')

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) return
    repeats.value = true
    endKind.value = 'weeks'
    weeksCount.value = 4
    monthsCount.value = 4
    untilDate.value = ''
  }
)

const repeat = computed(() => {
  if (!repeats.value) return { mode: 'none' }
  if (endKind.value === 'weeks') return { mode: 'weeks', count: Number(weeksCount.value) }
  if (endKind.value === 'months') return { mode: 'months', count: Number(monthsCount.value) }
  return { mode: 'until', until: untilDate.value }
})

const minUntil = computed(() => formatYmd(props.weekStart))
const maxUntil = computed(() => formatYmd(addDays(props.weekStart, MAX_SPAN_DAYS - 1)))

// What's wrong with the current choice, if anything — Save stays
// disabled until this is empty (the server re-checks all of it).
const problem = computed(() => {
  const r = repeat.value
  if (r.mode === 'weeks' && !(Number.isInteger(r.count) && r.count >= 1 && r.count <= 52)) {
    return 'Enter a number of weeks between 1 and 52.'
  }
  if (r.mode === 'months' && !(Number.isInteger(r.count) && r.count >= 1 && r.count <= 12)) {
    return 'Enter a number of months between 1 and 12.'
  }
  if (r.mode === 'until') {
    if (!r.until) return 'Pick an end date.'
    if (r.until < minUntil.value) return 'The end date must be on or after the start of this week.'
    if (r.until > maxUntil.value) return 'You can set availability at most one year ahead at once.'
  }
  return ''
})

const dayFmt = { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }
const summary = computed(() => {
  if (problem.value) return ''
  const from = props.weekStart.toLocaleDateString(undefined, dayFmt)
  const to = (repeat.value.mode === 'until'
    ? parseYmd(repeat.value.until)
    : repeatEndDate(props.weekStart, repeat.value)
  ).toLocaleDateString(undefined, dayFmt)
  return `This sets your availability from ${from} to ${to}, replacing anything already entered for those dates. Hours a shift is already booked for always stay available.`
})

const weekLabel = computed(() => {
  const end = addDays(props.weekStart, 6)
  const f = { day: 'numeric', month: 'short' }
  return `${props.weekStart.toLocaleDateString(undefined, f)} – ${end.toLocaleDateString(undefined, f)}`
})

function confirm() {
  if (problem.value || props.saving) return
  emit('confirm', repeat.value)
}
</script>

<template>
  <div v-if="open" class="overlay" @click.self="emit('cancel')">
    <div class="sheet" role="dialog" aria-label="Repeat this availability?">
      <h2 class="title">Repeat this availability?</h2>

      <fieldset class="group">
        <label class="radio-row">
          <input v-model="repeats" type="radio" :value="false" />
          <span>Doesn't repeat — only {{ weekLabel }}</span>
        </label>
        <label class="radio-row">
          <input v-model="repeats" type="radio" :value="true" />
          <span>Repeats weekly</span>
        </label>
      </fieldset>

      <fieldset v-if="repeats" class="group group--indented">
        <legend class="legend">Ends</legend>

        <label class="radio-row">
          <input v-model="endKind" type="radio" value="weeks" />
          <span>After</span>
          <input
            v-model="weeksCount"
            type="number"
            min="1"
            max="52"
            class="number-input"
            :class="{ dim: endKind !== 'weeks' }"
            @focus="endKind = 'weeks'"
          />
          <span>weeks</span>
        </label>

        <label class="radio-row">
          <input v-model="endKind" type="radio" value="months" />
          <span>After</span>
          <input
            v-model="monthsCount"
            type="number"
            min="1"
            max="12"
            class="number-input"
            :class="{ dim: endKind !== 'months' }"
            @focus="endKind = 'months'"
          />
          <span>months</span>
        </label>

        <label class="radio-row">
          <input v-model="endKind" type="radio" value="until" />
          <span>On</span>
          <input
            v-model="untilDate"
            type="date"
            class="date-input"
            :min="minUntil"
            :max="maxUntil"
            :class="{ dim: endKind !== 'until' }"
            @focus="endKind = 'until'"
          />
        </label>
      </fieldset>

      <p v-if="problem" class="note note--problem">{{ problem }}</p>
      <p v-else class="note">{{ summary }}</p>

      <p v-if="error" class="error-banner" role="alert">{{ error }}</p>

      <div class="actions">
        <button class="button button--secondary" :disabled="saving" @click="emit('cancel')">Cancel</button>
        <button class="button button--primary" :disabled="!!problem || saving" @click="confirm">
          {{ saving ? 'Saving…' : 'Save' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(28, 37, 48, 0.5);
  display: flex;
  align-items: flex-end;
  z-index: 40;
}

.sheet {
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  background: var(--color-paper);
  border-radius: 16px 16px 0 0;
  padding: 1.25rem 1.25rem calc(1.5rem + env(safe-area-inset-bottom));
}

.title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.1rem;
  margin: 0 0 1rem;
  color: var(--color-ink);
}

.group {
  border: none;
  padding: 0;
  margin: 0 0 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.group--indented {
  margin-left: 1.5rem;
}

.legend {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-slate);
  padding: 0;
  margin-bottom: 0.4rem;
}

.radio-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--color-ink);
}

.number-input,
.date-input {
  padding: 0.35rem 0.5rem;
  font-size: 0.88rem;
  font-family: inherit;
  border: 1px solid var(--color-line);
  border-radius: 6px;
  background: #fff;
}

.number-input {
  width: 4.2rem;
}

/* Not disabled when it isn't the chosen end rule: a disabled input can't
   take focus, so tapping straight into it wouldn't select its radio. */
.number-input.dim,
.date-input.dim {
  opacity: 0.5;
}

.note {
  font-size: 0.8rem;
  color: var(--color-slate);
  line-height: 1.5;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  padding: 0.7rem 0.85rem;
  margin: 0 0 0.9rem;
}

.note--problem {
  color: var(--color-danger);
  border-color: rgba(181, 83, 63, 0.35);
}

.error-banner {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.7rem 0.9rem;
  font-size: 0.82rem;
  margin: 0 0 0.9rem;
}

.actions {
  display: flex;
  gap: 0.75rem;
}

.button {
  flex: 1;
  padding: 0.7rem;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
}

.button--secondary {
  color: var(--color-ink);
  background: #fff;
  border: 1px solid var(--color-line);
}

.button--primary {
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
}

.button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
