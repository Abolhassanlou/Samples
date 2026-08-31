<script setup>
import { ref, onMounted } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import {
  fetchRoles,
  fetchPermissions,
  createRole,
  updateRole,
  deleteRole,
} from '@/api/authorization'

const roles = ref([])
const permissions = ref([])

const loading = ref(true)
const errorMessage = ref('')

// Which role id's permission editor is currently expanded.
const expandedRoleId = ref(null)
const editingPermissions = ref([])

// New-role creation form state.
const newRoleName = ref('')
const newRolePermissions = ref([])
const creatingRole = ref(false)

async function loadAll() {
  loading.value = true
  errorMessage.value = ''
  try {
    const [r, p] = await Promise.all([fetchRoles(), fetchPermissions()])
    roles.value = r
    permissions.value = p
  } catch {
    errorMessage.value = 'Could not load roles. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

function toggleExpand(role) {
  if (expandedRoleId.value === role.id) {
    expandedRoleId.value = null
    return
  }
  expandedRoleId.value = role.id
  editingPermissions.value = [...role.permissions]
}

async function handleSaveRole(role) {
  await updateRole(role.id, { permissions: editingPermissions.value })
  expandedRoleId.value = null
  await loadAll()
}

async function handleDeleteRole(role) {
  await deleteRole(role.id)
  await loadAll()
}

async function handleCreateRole() {
  if (!newRoleName.value.trim()) return

  creatingRole.value = true
  try {
    await createRole({ name: newRoleName.value.trim(), permissions: newRolePermissions.value })
    newRoleName.value = ''
    newRolePermissions.value = []
    await loadAll()
  } finally {
    creatingRole.value = false
  }
}
</script>

<template>
  <AppShell>
    <h1 class="page-title">Roles</h1>
    <p class="page-lead">
      Define what each role can do. Roles are fully custom — create as many as your company
      needs, with any combination of permissions.
    </p>

    <p v-if="errorMessage" class="error-banner" role="alert">{{ errorMessage }}</p>
    <p v-else-if="loading" class="loading-note">Loading…</p>

    <section v-else class="panel">
      <div v-for="role in roles" :key="role.id" class="role-row">
        <div class="role-row-header">
          <div>
            <span class="role-row-name">{{ role.name }}</span>
            <span v-if="role.is_system" class="system-badge">System</span>
            <span class="role-row-count">{{ role.permissions.length }} permissions</span>
          </div>
          <div class="role-row-actions">
            <button class="text-button" @click="toggleExpand(role)">
              {{ expandedRoleId === role.id ? 'Close' : 'Manage' }}
            </button>
            <button
              v-if="!role.is_system"
              class="text-button text-button--danger"
              @click="handleDeleteRole(role)"
            >
              Delete
            </button>
          </div>
        </div>

        <div v-if="expandedRoleId === role.id" class="permission-editor">
          <label v-for="perm in permissions" :key="perm.id" class="permission-check">
            <input type="checkbox" :value="perm.name" v-model="editingPermissions" />
            {{ perm.name }}
          </label>
          <button class="save-button" @click="handleSaveRole(role)">Save permissions</button>
        </div>
      </div>

      <div class="new-role-form">
        <h3 class="new-role-title">New role</h3>
        <input
          v-model="newRoleName"
          type="text"
          class="new-role-input"
          placeholder="e.g. Regional Supervisor"
        />
        <div class="permission-editor permission-editor--flat">
          <label v-for="perm in permissions" :key="perm.id" class="permission-check">
            <input type="checkbox" :value="perm.name" v-model="newRolePermissions" />
            {{ perm.name }}
          </label>
        </div>
        <button
          class="save-button"
          :disabled="!newRoleName.trim() || creatingRole"
          @click="handleCreateRole"
        >
          {{ creatingRole ? 'Creating…' : 'Create role' }}
        </button>
      </div>
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
  max-width: 60ch;
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

.role-row {
  padding: 0.9rem 0;
  border-bottom: 1px solid var(--color-line);
}

.role-row-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.role-row-name {
  font-weight: 600;
  margin-right: 0.6rem;
}

.system-badge {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--color-slate);
  border: 1px solid var(--color-line);
  border-radius: 4px;
  padding: 0.1rem 0.4rem;
  margin-right: 0.6rem;
}

.role-row-count {
  font-size: 0.8rem;
  color: var(--color-slate);
}

.role-row-actions {
  display: flex;
  gap: 1rem;
}

.text-button {
  background: none;
  border: none;
  color: var(--color-amber-dark);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}

.text-button--danger {
  color: var(--color-danger);
}

.permission-editor {
  margin-top: 0.9rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.5rem;
  background: var(--color-paper);
  border-radius: 8px;
  padding: 1rem;
}

.permission-check {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.82rem;
}

.save-button {
  grid-column: 1 / -1;
  justify-self: start;
  margin-top: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.45rem 0.9rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.save-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.new-role-form {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px dashed var(--color-line);
}

.new-role-title {
  font-family: var(--font-display);
  font-size: 0.95rem;
  margin: 0 0 0.75rem;
}

.new-role-input {
  width: 100%;
  max-width: 320px;
  padding: 0.5rem 0.7rem;
  font-size: 0.9rem;
  border: 1px solid var(--color-line);
  border-radius: 6px;
  margin-bottom: 0.75rem;
}
</style>
