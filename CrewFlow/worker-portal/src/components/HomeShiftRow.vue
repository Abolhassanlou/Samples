<script setup>
import { computed } from 'vue'
import { relativeDayLabel, formatTimeRange } from '@/utils/homeDashboard'

const props = defineProps({
  item: { type: Object, required: true }, // an assignment: { change_note, shift: { title, starts_at, ends_at, location_address } }
  now: { type: Date, required: true },
  cta: { type: String, default: '' }, // a short action word on the right, e.g. "Confirm"
})

const emit = defineEmits(['open'])

const dayLabel = computed(() => relativeDayLabel(new Date(props.item.shift.starts_at), props.now))
const timeRange = computed(() => formatTimeRange(props.item.shift))
</script>

<template>
  <button class="row" @click="emit('open')">
    <span class="row-main">
      <span class="row-day">{{ dayLabel }}</span>
      <span class="row-title">{{ item.shift.title }}</span>
      <span class="row-meta">
        {{ timeRange }}<template v-if="item.shift.location_address"> · {{ item.shift.location_address }}</template>
      </span>
      <!-- Set only when an edit sent a confirmed shift back for
           re-confirmation — exactly what changed, in plain words. -->
      <span v-if="item.change_note" class="row-note">{{ item.change_note }}</span>
    </span>
    <span v-if="cta" class="row-cta">{{ cta }}</span>
  </button>
</template>

<style scoped>
.row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  width: 100%;
  text-align: left;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 12px;
  padding: 0.8rem 0.95rem;
  font-family: inherit;
  cursor: pointer;
}

.row-main {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.row-day {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-amber-dark);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.row-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-ink);
}

.row-meta {
  font-size: 0.78rem;
  color: var(--color-slate);
}

.row-note {
  margin-top: 0.35rem;
  font-size: 0.76rem;
  color: var(--color-amber-dark);
  background: rgba(224, 151, 58, 0.14);
  border-radius: 6px;
  padding: 0.3rem 0.5rem;
  line-height: 1.4;
}

.row-cta {
  flex-shrink: 0;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-ink);
  background: var(--color-amber);
  border-radius: 999px;
  padding: 0.3rem 0.8rem;
}
</style>
