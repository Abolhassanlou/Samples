<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="page">
    <header class="topbar">
      <span class="brand-mark">CrewFlow</span>
      <button class="logout-button" @click="handleLogout">Sign out</button>
    </header>

    <main class="content">
      <h1 class="greeting">Hi, {{ auth.user?.name }}</h1>
      <p class="lead">
        Your account is set up. This is a placeholder — shift browsing, your assignments, and
        document upload are built next.
      </p>
    </main>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: var(--color-paper);
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: var(--color-ink);
  color: var(--color-paper);
}

.brand-mark {
  font-family: var(--font-display);
  font-weight: 800;
  font-size: 1.1rem;
}

.logout-button {
  padding: 0.4rem 0.9rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-ink);
  background: var(--color-paper);
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.content {
  padding: 1.75rem 1.25rem;
  max-width: 480px;
  margin: 0 auto;
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
