<script setup>
import { RouterLink } from 'vue-router'

const tabs = [
  { to: '/', label: 'Home', icon: 'home' },
  { to: '/jobs', label: 'Jobs', icon: 'jobs' },
  { to: '/calendar', label: 'Calendar', icon: 'calendar' },
  { to: '/chat', label: 'Chat', icon: 'chat' },
  { to: '/profile', label: 'Profile', icon: 'profile' },
]
</script>

<template>
  <div class="shell">
    <main class="content">
      <slot />
    </main>

    <nav class="tabbar">
      <RouterLink
        v-for="tab in tabs"
        :key="tab.to"
        :to="tab.to"
        class="tab"
        active-class="tab--active"
      >
        <svg
          v-if="tab.icon === 'home'"
          class="tab-icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
        >
          <path d="M4 11.5L12 4l8 7.5" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M6 10v9h12v-9" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

        <svg
          v-else-if="tab.icon === 'jobs'"
          class="tab-icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
        >
          <rect x="3.5" y="7" width="17" height="12" rx="1.8" />
          <path d="M8.5 7V5.5a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2V7" stroke-linecap="round" />
        </svg>

        <svg
          v-else-if="tab.icon === 'calendar'"
          class="tab-icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
        >
          <rect x="3.5" y="5" width="17" height="15" rx="1.8" />
          <path d="M3.5 9.5h17" stroke-linecap="round" />
          <path d="M8 3.5v3M16 3.5v3" stroke-linecap="round" />
        </svg>

        <svg
          v-else-if="tab.icon === 'chat'"
          class="tab-icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
        >
          <path
            d="M4 5.5h16v10.5H9l-4 3.5v-3.5H4z"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>

        <svg
          v-else-if="tab.icon === 'profile'"
          class="tab-icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
        >
          <circle cx="12" cy="8.2" r="3.2" />
          <path d="M5 20c0-3.6 2.9-6 7-6s7 2.4 7 6" stroke-linecap="round" />
        </svg>

        <span class="tab-label">{{ tab.label }}</span>
      </RouterLink>
    </nav>
  </div>
</template>

<style scoped>
.shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--color-paper);
}

.content {
  flex: 1;
  padding-bottom: 72px; /* clears the fixed tabbar */
}

.tabbar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  background: #fff;
  border-top: 1px solid var(--color-line);
  padding: 0.4rem 0.25rem calc(0.4rem + env(safe-area-inset-bottom));
  z-index: 10;
}

.tab {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  padding: 0.35rem 0;
  color: var(--color-slate);
  text-decoration: none;
  font-size: 0.68rem;
  font-weight: 600;
  border-radius: 10px;
}

.tab-icon {
  width: 22px;
  height: 22px;
}

.tab--active {
  color: var(--color-amber-dark);
}

.tab-label {
  line-height: 1;
}
</style>
