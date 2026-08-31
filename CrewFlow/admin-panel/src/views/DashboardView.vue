<script setup>
import { useAuthStore } from '@/stores/auth'
import AppShell from '@/components/layout/AppShell.vue'

const auth = useAuthStore()
</script>

<template>
  <AppShell>
    <h1 class="page-title">Welcome, {{ auth.user?.name }}</h1>
    <p class="page-lead">
      This is a placeholder — shift/worker stats are built next. Start with
      <RouterLink to="/users">Users</RouterLink> to see who's registered and manage access.
    </p>
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
}

.page-lead :deep(a) {
  color: var(--color-amber-dark);
  font-weight: 600;
}
</style>
