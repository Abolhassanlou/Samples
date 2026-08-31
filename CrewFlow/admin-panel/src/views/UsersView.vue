<script setup>
import { ref, onMounted, computed } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import { fetchUsers, fetchRoles, assignRole, removeRole } from '@/api/authorization'

const users = ref([])
const roles = ref([])

const loading = ref(true)
const errorMessage = ref('')
const searchQuery = ref('')

// Which role name is queued up to assign, per user id.
const pendingRoleByUser = ref({})

const roleNames = computed(() => roles.value.map((r) => r.name))

const filteredUsers = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return users.value

  return users.value.filter((user) => {
    return (
      user.name.toLowerCase().includes(q) ||
      user.email.toLowerCase().includes(q) ||
      (user.personnel_number || '').toLowerCase().includes(q)
    )
  })
})

async function loadAll() {
  loading.value = true
  errorMessage.value = ''
  try {
    const [u, r] = await Promise.all([fetchUsers(), fetchRoles()])
    users.value = u
    roles.value = r
  } catch {
    errorMessage.value = 'Could not load users. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

function availableRolesFor(user) {
  return roleNames.value.filter((name) => !user.roles.includes(name))
}

async function handleAssign(user) {
  const roleName = pendingRoleByUser.value[user.id]
  if (!roleName) return

  await assignRole(user.id, roleName)
  pendingRoleByUser.value[user.id] = ''
  await loadAll()
}

async function handleRemove(user, roleName) {
  await removeRole(user.id, roleName)
  await loadAll()
}
</script>

<template>
  <AppShell>
    <h1 class="page-title">Users</h1>
    <p class="page-lead">
      See who's registered and grant them roles. Manage what each role can do on the
      <RouterLink to="/roles">Roles</RouterLink> page.
    </p>

    <p v-if="errorMessage" class="error-banner" role="alert">{{ errorMessage }}</p>
    <p v-else-if="loading" class="loading-note">Loading…</p>

    <section v-else class="panel">
      <div class="table-toolbar">
        <input
          v-model="searchQuery"
          type="search"
          class="search-input"
          placeholder="Search by name, email, or personnel number…"
        />
        <span class="result-count">{{ filteredUsers.length }} of {{ users.length }}</span>
      </div>

      <table class="users-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Grant role</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in filteredUsers" :key="user.id">
            <td class="personnel-number">{{ user.personnel_number }}</td>
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span v-for="roleName in user.roles" :key="roleName" class="role-chip">
                {{ roleName }}
                <button
                  class="role-chip-remove"
                  :aria-label="`Remove ${roleName} from ${user.name}`"
                  @click="handleRemove(user, roleName)"
                >
                  ×
                </button>
              </span>
              <span v-if="user.roles.length === 0" class="no-roles">No roles yet</span>
            </td>
            <td>
              <div class="grant-row">
                <select v-model="pendingRoleByUser[user.id]" class="role-select">
                  <option value="" disabled>Choose a role…</option>
                  <option v-for="name in availableRolesFor(user)" :key="name" :value="name">
                    {{ name }}
                  </option>
                </select>
                <button
                  class="grant-button"
                  :disabled="!pendingRoleByUser[user.id]"
                  @click="handleAssign(user)"
                >
                  Add
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="filteredUsers.length === 0">
            <td colspan="5" class="empty-row">No users match "{{ searchQuery }}".</td>
          </tr>
        </tbody>
      </table>
    </section>
  </AppShell>
</template>

<style scoped>
.page-title {
  font-family: var(--font-display);
  font-weight: 700;
  margin: 0 0 0.35rem;
}

.page-lead {
  color: var(--color-slate);
  margin: 0 0 2rem;
}

.page-lead :deep(a) {
  color: var(--color-amber-dark);
  font-weight: 600;
}

.error-banner {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
}

.loading-note {
  color: var(--color-slate);
}

.panel {
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 1.5rem;
}

.table-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.1rem;
}

.search-input {
  flex: 1;
  max-width: 340px;
  padding: 0.5rem 0.8rem;
  font-size: 0.88rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  outline: none;
}

.search-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
}

.result-count {
  font-size: 0.8rem;
  color: var(--color-slate);
  white-space: nowrap;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th {
  text-align: left;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--color-slate);
  padding: 0 0.75rem 0.6rem;
  border-bottom: 1px solid var(--color-line);
}

.users-table td {
  padding: 0.75rem;
  border-bottom: 1px solid var(--color-line);
  font-size: 0.9rem;
  vertical-align: middle;
}

.personnel-number {
  font-family: var(--font-mono);
  font-size: 0.82rem;
  color: var(--color-slate);
}

.empty-row {
  text-align: center;
  color: var(--color-slate);
  font-style: italic;
  padding: 1.5rem 0;
}

.role-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  background: rgba(224, 151, 58, 0.14);
  color: var(--color-amber-dark);
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  margin: 0.15rem 0.3rem 0.15rem 0;
}

.role-chip-remove {
  background: none;
  border: none;
  color: inherit;
  font-size: 0.95rem;
  line-height: 1;
  cursor: pointer;
  padding: 0;
}

.no-roles {
  color: var(--color-slate);
  font-size: 0.85rem;
  font-style: italic;
}

.grant-row {
  display: flex;
  gap: 0.5rem;
}

.role-select {
  font-size: 0.85rem;
  padding: 0.35rem 0.5rem;
  border: 1px solid var(--color-line);
  border-radius: 6px;
  background: #fff;
}

.grant-button {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35rem 0.7rem;
  color: var(--color-ink);
  background: var(--color-paper);
  border: 1px solid var(--color-line);
  border-radius: 6px;
  cursor: pointer;
}

.grant-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
