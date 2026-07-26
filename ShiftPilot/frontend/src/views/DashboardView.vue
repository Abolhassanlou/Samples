<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout(): Promise<void> {
  await authStore.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <main class="dashboard">
    <header class="dashboard__header">
      <div>
        <span class="dashboard__brand">ShiftPilot</span>
        <h1>Dashboard</h1>
      </div>

      <button
        class="logout-button"
        type="button"
        :disabled="authStore.isLoading"
        @click="handleLogout"
      >
        Log out
      </button>
    </header>

    <section class="welcome-card">
      <p class="welcome-card__label">Welcome back</p>

      <h2>{{ authStore.displayName }}</h2>

      <p v-if="authStore.user">
        {{ authStore.user.email }}
      </p>
    </section>

    <section class="dashboard__grid">
      <article class="dashboard-card">
        <span>Work schedule</span>
        <strong>Coming soon</strong>
      </article>

      <article class="dashboard-card">
        <span>Availability</span>
        <strong>Coming soon</strong>
      </article>

      <article class="dashboard-card">
        <span>Company locations</span>
        <strong>Coming soon</strong>
      </article>
    </section>
  </main>
</template>

<style scoped>
.dashboard {
  min-height: 100vh;
  padding: 2rem;
  direction: ltr;
  background: var(--color-background);
}

.dashboard__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 72rem;
  margin: 0 auto 2rem;
}

.dashboard__header h1 {
  margin: 0.4rem 0 0;
  color: var(--color-heading);
}

.dashboard__brand {
  color: var(--color-primary);
  font-weight: 800;
  letter-spacing: 0.04em;
}

.logout-button {
  padding: 0.7rem 1.25rem;
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  color: var(--color-heading);
  background: var(--color-surface);
  cursor: pointer;
}

.logout-button:disabled {
  cursor: wait;
  opacity: 0.6;
}

.welcome-card,
.dashboard-card {
  border: 1px solid var(--color-border);
  border-radius: 1.25rem;
  background: var(--color-surface);
  box-shadow: var(--shadow-sm);
}

.welcome-card {
  max-width: 72rem;
  margin: 0 auto 1.5rem;
  padding: 2rem;
}

.welcome-card__label {
  margin: 0 0 0.5rem;
  color: var(--color-text-muted);
}

.welcome-card h2 {
  margin: 0 0 0.5rem;
  color: var(--color-heading);
}

.welcome-card p:last-child {
  margin-bottom: 0;
}

.dashboard__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  max-width: 72rem;
  margin: 0 auto;
}

.dashboard-card {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1.5rem;
}

.dashboard-card strong {
  color: var(--color-primary);
}

@media (max-width: 48rem) {
  .dashboard {
    padding: 1rem;
  }

  .dashboard__grid {
    grid-template-columns: 1fr;
  }
}
</style>
