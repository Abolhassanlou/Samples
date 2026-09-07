<script setup>
import AppShell from '@/components/layout/AppShell.vue'
</script>

<template>
  <AppShell>
    <header class="header">
      <h1 class="title">Calendar</h1>
    </header>

    <div class="body">
      <p class="lead">This section is coming next.</p>
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

.body {
  padding: 0.5rem 1.25rem 1.5rem;
}

.lead {
  color: var(--color-slate);
  font-size: 0.9rem;
}
</style>
