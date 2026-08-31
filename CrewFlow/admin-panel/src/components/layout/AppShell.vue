<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter, RouterLink } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const navItems = [
  { to: '/', label: 'Dashboard', icon: 'dashboard' },
  { to: '/workers', label: 'Workers', icon: 'workers' },
  { to: '/users', label: 'Users', icon: 'users' },
  { to: '/roles', label: 'Roles', icon: 'roles' },
]

function handleLogout() {
  auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="shell">
    <aside class="sidebar">
      <div class="sidebar-brand">CrewFlow</div>
      <nav class="sidebar-nav">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="nav-link"
          active-class="nav-link--active"
        >
          <svg
            v-if="item.icon === 'dashboard'"
            class="nav-icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.2" />
            <rect x="13.5" y="3.5" width="7" height="7" rx="1.2" />
            <rect x="3.5" y="13.5" width="7" height="7" rx="1.2" />
            <rect x="13.5" y="13.5" width="7" height="7" rx="1.2" />
          </svg>

          <svg
            v-else-if="item.icon === 'workers'"
            class="nav-icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <rect x="3.5" y="5" width="17" height="14" rx="2" />
            <circle cx="9" cy="10.5" r="2.2" />
            <path d="M6.3 15.5c0-1.7 1.3-2.8 2.7-2.8s2.7 1.1 2.7 2.8" stroke-linecap="round" />
            <path d="M14.5 9.5h3.2M14.5 12.5h3.2M14.5 15.5h1.8" stroke-linecap="round" />
          </svg>

          <svg
            v-else-if="item.icon === 'users'"
            class="nav-icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <circle cx="9" cy="8" r="3.2" />
            <path d="M3.5 20c0-3.3 2.5-5.5 5.5-5.5s5.5 2.2 5.5 5.5" />
            <circle cx="17" cy="8.5" r="2.5" />
            <path d="M15.2 14.8c2.6 0.2 4.5 2.2 4.5 5.2" />
          </svg>

          <svg
            v-else-if="item.icon === 'roles'"
            class="nav-icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <path d="M12 3.5l6.5 2.6v5.4c0 4-2.7 7.2-6.5 8.5-3.8-1.3-6.5-4.5-6.5-8.5V6.1L12 3.5z" />
            <path d="M9.2 12l1.9 1.9 3.7-3.9" stroke-linecap="round" stroke-linejoin="round" />
          </svg>

          {{ item.label }}
        </RouterLink>
      </nav>
    </aside>

    <div class="main">
      <header class="topbar">
        <span class="topbar-company">{{ auth.companyCode }}</span>
        <div class="topbar-user">
          <span class="topbar-name">{{ auth.user?.name }}</span>
          <button class="logout-button" @click="handleLogout">Sign out</button>
        </div>
      </header>

      <main class="content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.shell {
  display: grid;
  grid-template-columns: 220px 1fr;
  min-height: 100vh;
}

.sidebar {
  background: var(--color-ink);
  color: var(--color-paper);
  padding: 1.75rem 1.25rem;
}

.sidebar-brand {
  font-family: var(--font-display);
  font-weight: 800;
  font-size: 1.15rem;
  margin-bottom: 2rem;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.55rem 0.75rem;
  border-radius: 6px;
  color: #b9c2cb;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: background 0.15s ease, color 0.15s ease;
}

.nav-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.06);
  color: var(--color-paper);
}

.nav-link--active {
  background: rgba(224, 151, 58, 0.15);
  color: var(--color-amber);
}

.main {
  display: flex;
  flex-direction: column;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2rem;
  background: #fff;
  border-bottom: 1px solid var(--color-line);
}

.topbar-company {
  font-family: var(--font-mono);
  font-size: 0.85rem;
  color: var(--color-amber-dark);
}

.topbar-user {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 0.9rem;
}

.logout-button {
  padding: 0.4rem 0.9rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-ink);
  background: var(--color-paper);
  border: 1px solid var(--color-line);
  border-radius: 6px;
  cursor: pointer;
}

.content {
  flex: 1;
  padding: 2rem;
}
</style>
