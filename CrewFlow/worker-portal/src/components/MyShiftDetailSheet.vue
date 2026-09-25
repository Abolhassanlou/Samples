<script setup>
import { ref } from 'vue'
import { withdrawInterest, confirmAssignment, requestCancellation } from '@/api/shifts'

const props = defineProps({
  item: { type: Object, default: null }, // { kind: 'interest'|'assignment', shift, ...fields } | null
})

const emit = defineEmits(['close', 'updated'])

const busyKey = ref(null)
const actionError = ref('')

function formatDateRange(shift) {
  if (!shift?.starts_at) return ''
  const start = new Date(shift.starts_at)
  const end = shift.ends_at ? new Date(shift.ends_at) : null
  const dateFmt = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }
  const timeFmt = { hour: '2-digit', minute: '2-digit' }
  const datePart = start.toLocaleDateString(undefined, dateFmt)
  const startTime = start.toLocaleTimeString(undefined, timeFmt)
  const endTime = end ? end.toLocaleTimeString(undefined, timeFmt) : ''
  return endTime ? `${datePart}, ${startTime} – ${endTime}` : `${datePart}, ${startTime}`
}

function rateLabel(shift) {
  if (!shift) return ''
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

async function handleWithdraw() {
  busyKey.value = 'withdraw'
  actionError.value = ''
  try {
    await withdrawInterest(props.item.shift_id)
    emit('updated')
    emit('close')
  } catch (error) {
    actionError.value = error.response?.data?.message || 'Could not withdraw. Please try again.'
  } finally {
    busyKey.value = null
  }
}

async function handleConfirm() {
  busyKey.value = 'confirm'
  actionError.value = ''
  try {
    await confirmAssignment(props.item.id)
    emit('updated')
    emit('close')
  } catch (error) {
    actionError.value = error.response?.data?.message || 'Could not confirm. Please try again.'
  } finally {
    busyKey.value = null
  }
}

async function handleRequestCancellation() {
  busyKey.value = 'cancel'
  actionError.value = ''
  try {
    await requestCancellation(props.item.id)
    emit('updated')
    emit('close')
  } catch (error) {
    actionError.value = error.response?.data?.message || 'Could not request cancellation. Please try again.'
  } finally {
    busyKey.value = null
  }
}
</script>

<template>
  <div v-if="item" class="detail-overlay" @click.self="$emit('close')">
    <div class="detail-sheet">
      <div class="detail-header">
        <h2 class="detail-title">{{ item.shift?.title }}</h2>
        <button class="detail-close" @click="$emit('close')">✕</button>
      </div>

      <span class="status-badge" :class="statusClass(item)">{{ statusLabel(item) }}</span>

      <div v-if="item.change_note" class="change-note change-note--sheet">
        <strong>This shift was updated since you confirmed:</strong>
        <p>{{ item.change_note }}</p>
      </div>

      <p v-if="item.qualification_warning" class="qualification-warning">
        Note: you may not fully meet this shift's usual requirements.
      </p>

      <dl class="detail-list">
        <div class="detail-row">
          <dt>When</dt>
          <dd>{{ formatDateRange(item.shift) }}</dd>
        </div>
        <div v-if="item.shift?.location_address" class="detail-row">
          <dt>Location</dt>
          <dd>
            {{ item.shift.location_address }}
            <span v-if="item.shift.location_type === 'online'"> (online)</span>
          </dd>
        </div>
        <div v-if="item.role_name" class="detail-row">
          <dt>Role</dt>
          <dd>{{ item.role_name }}</dd>
        </div>
        <div v-if="rateLabel(item.shift)" class="detail-row">
          <dt>Rate</dt>
          <dd>{{ rateLabel(item.shift) }}</dd>
        </div>
        <div v-if="item.shift?.internal_contact_name" class="detail-row">
          <dt>Contact</dt>
          <dd>
            {{ item.shift.internal_contact_name }}
            <span v-if="item.shift.internal_contact_phone"> · {{ item.shift.internal_contact_phone }}</span>
          </dd>
        </div>
        <div v-if="item.shift?.client_contact_name" class="detail-row">
          <dt>On-site contact</dt>
          <dd>
            {{ item.shift.client_contact_name }}
            <span v-if="item.shift.client_contact_phone"> · {{ item.shift.client_contact_phone }}</span>
          </dd>
        </div>
        <div v-if="item.shift?.description" class="detail-row detail-row--wide">
          <dt>Description</dt>
          <dd class="detail-description">{{ item.shift.description }}</dd>
        </div>
      </dl>

      <p v-if="actionError" class="error-banner error-banner--in-sheet" role="alert">{{ actionError }}</p>

      <div class="detail-actions">
        <button
          v-if="item.kind === 'interest'"
          class="sheet-button sheet-button--danger"
          :disabled="busyKey === 'withdraw'"
          @click="handleWithdraw"
        >
          {{ busyKey === 'withdraw' ? 'Withdrawing…' : 'Withdraw interest' }}
        </button>
        <button
          v-if="item.kind === 'assignment' && item.status === 'pending_worker_confirmation'"
          class="sheet-button sheet-button--confirm"
          :disabled="busyKey === 'confirm'"
          @click="handleConfirm"
        >
          {{ busyKey === 'confirm' ? 'Confirming…' : 'Confirm' }}
        </button>
        <button
          v-if="item.kind === 'assignment' && item.status === 'confirmed'"
          class="sheet-button sheet-button--danger"
          :disabled="busyKey === 'cancel'"
          @click="handleRequestCancellation"
        >
          {{ busyKey === 'cancel' ? 'Requesting…' : 'Request cancellation' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
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

.change-note {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-amber-dark);
  background: rgba(224, 151, 58, 0.14);
  border-radius: 6px;
  padding: 0.4rem 0.6rem;
  margin: 0 0 0.5rem;
}

.change-note--sheet {
  margin: 0.75rem 0;
  padding: 0.7rem 0.85rem;
}

.change-note--sheet strong {
  display: block;
  font-size: 0.8rem;
  margin-bottom: 0.3rem;
}

.change-note--sheet p {
  font-weight: 400;
  margin: 0;
  font-size: 0.85rem;
  color: var(--color-ink);
}

.error-banner {
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

.sheet-button {
  width: 100%;
  padding: 0.7rem;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  border: 1px solid var(--color-line);
  background: #fff;
  cursor: pointer;
}

.sheet-button--danger {
  color: var(--color-danger);
  border-color: rgba(181, 83, 63, 0.35);
}

.sheet-button--confirm {
  color: #fff;
  background: var(--color-green);
  border: none;
}

.sheet-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
