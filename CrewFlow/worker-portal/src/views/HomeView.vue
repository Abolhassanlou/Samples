<script setup>
import { useAuthStore } from '@/stores/auth'
import AppShell from '@/components/layout/AppShell.vue'

const auth = useAuthStore()
</script>

<template>
  <AppShell>
    <header class="header">
      <span class="brand-mark">CrewFlow</span>
    </header>

    <div class="body">
      <h1 class="greeting">Hi, {{ auth.user?.name }}</h1>
      <p class="lead">
        This is a placeholder — today's shifts, upcoming assignments, and quick actions are
        built next.
      </p>
    </div>
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
}

.greeting {
  font-family: var(--font-display);
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.lead {
  color: var(--color-slate);
  font-size: 0.9rem;
  line-height: 1.5;
}
</style>
