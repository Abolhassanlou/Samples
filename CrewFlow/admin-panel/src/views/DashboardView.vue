<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppShell from '@/components/layout/AppShell.vue'
import { fetchExpiringDocuments } from '@/api/workers'

const auth = useAuthStore()

const expiringDocuments = ref([])
const loading = ref(true)
const loadError = ref('')

const hasAccess = computed(() => auth.can('users.manage'))

async function loadExpiringDocuments() {
  if (!hasAccess.value) {
    loading.value = false
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    expiringDocuments.value = await fetchExpiringDocuments()
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Could not load expiring documents.'
  } finally {
    loading.value = false
  }
}

onMounted(loadExpiringDocuments)

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

const STATUS_LABELS = {
  pending: 'Pending',
  valid: 'Valid',
  expired: 'Expired',
  not_required: 'Not required',
  rejected: 'Rejected',
}
</script>

<template>
  <AppShell>
    <h1 class="page-title">Welcome, {{ auth.user?.name }}</h1>
    <p class="page-lead">
      Start with
      <RouterLink to="/users">Users</RouterLink> to see who's registered and manage access, or
      <RouterLink to="/shifts">Shifts</RouterLink> to staff upcoming work.
    </p>

    <section v-if="hasAccess" class="panel">
      <h2 class="panel-title">Work authorization needs attention</h2>
      <p class="panel-sublead">
        Every worker whose passport/ID/visa expiry is already past, or due within the next 30
        days. A daily background check also flips a worker's status to "expired" automatically
        once the date passes — see the
        <RouterLink to="/workers">Workers</RouterLink> page's work-authorization filter to browse
        by status directly.
      </p>

      <p v-if="loading" class="loading-note">Loading…</p>
      <p v-else-if="loadError" class="error-banner" role="alert">{{ loadError }}</p>

      <div v-else-if="expiringDocuments.length > 0" class="document-list">
        <RouterLink
          v-for="doc in expiringDocuments"
          :key="doc.user_id"
          :to="`/workers/${doc.user_id}`"
          class="document-row"
          :class="{ 'document-row--expired': doc.is_expired }"
        >
          <div>
            <span class="worker-name">{{ doc.name }}</span>
            <span class="worker-meta">
              {{ doc.work_authorization_type || 'No type on file' }} ·
              {{ STATUS_LABELS[doc.work_authorization_status] || doc.work_authorization_status }}
            </span>
          </div>
          <span class="expiry-badge" :class="{ 'expiry-badge--expired': doc.is_expired }">
            {{ doc.is_expired ? 'Expired' : 'Expires' }} {{ formatDate(doc.work_authorization_expiry_date) }}
          </span>
        </RouterLink>
      </div>
      <p v-else class="empty-note">Nothing expiring in the next 30 days.</p>
    </section>
  </AppShell>
</template>

<style scoped>
.page-title {
  font-family: var(--font-display);
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.page-lead {
  color: var(--color-slate);
  margin: 0 0 1.5rem;
}

.page-lead :deep(a) {
  color: var(--color-amber-dark);
  font-weight: 600;
}

.panel {
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 1.5rem;
  max-width: 700px;
}

.panel-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.02rem;
  margin: 0 0 0.3rem;
}

.panel-sublead {
  font-size: 0.83rem;
  color: var(--color-slate);
  margin: 0 0 1.1rem;
  line-height: 1.5;
}

.panel-sublead :deep(a) {
  color: var(--color-amber-dark);
  font-weight: 600;
}

.loading-note {
  color: var(--color-slate);
  font-size: 0.85rem;
}

.error-banner {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.82rem;
}

.document-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.document-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 0.9rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  text-decoration: none;
  color: inherit;
}

.document-row--expired {
  border-color: rgba(181, 83, 63, 0.4);
  background: rgba(181, 83, 63, 0.04);
}

.worker-name {
  display: block;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--color-ink);
}

.worker-meta {
  display: block;
  font-size: 0.78rem;
  color: var(--color-slate);
  margin-top: 0.1rem;
}

.expiry-badge {
  flex-shrink: 0;
  font-size: 0.76rem;
  font-weight: 600;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  background: rgba(224, 151, 58, 0.18);
  color: var(--color-amber-dark);
  white-space: nowrap;
}

.expiry-badge--expired {
  background: rgba(181, 83, 63, 0.15);
  color: var(--color-danger);
}

.empty-note {
  color: var(--color-slate);
  font-style: italic;
  font-size: 0.85rem;
}
</style>
