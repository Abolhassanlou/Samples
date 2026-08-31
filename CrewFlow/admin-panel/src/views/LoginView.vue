<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const companyCode = ref('')
const email = ref('')
const password = ref('')

// Staggered widths for the roster-bars signature motif — deliberately
// irregular, like a real shift board rather than a uniform bar chart.
const rosterBars = [62, 88, 45, 95, 70, 55, 82]

async function handleSubmit() {
  const success = await auth.login({
    companyCode: companyCode.value.trim().toLowerCase(),
    email: email.value.trim(),
    password: password.value,
  })

  if (success) {
    router.push(route.query.redirect || '/')
  }
}
</script>

<template>
  <div class="login-screen">
    <aside class="brand-panel">
      <div class="brand-mark">CrewFlow</div>
      <p class="brand-tagline">Every shift, staffed. Every worker, in sync.</p>

      <div class="roster" aria-hidden="true">
        <span
          v-for="(width, i) in rosterBars"
          :key="i"
          class="roster-bar"
          :style="{ '--w': width + '%', '--delay': i * 90 + 'ms' }"
        />
      </div>
    </aside>

    <main class="form-panel">
      <form class="login-form" @submit.prevent="handleSubmit">
        <h1 class="form-title">Sign in</h1>
        <p class="form-subtitle">Enter your company's workspace to continue.</p>

        <label class="field">
          <span class="field-label">Company code</span>
          <input
            v-model="companyCode"
            type="text"
            class="field-input field-input--mono"
            placeholder="acme2024"
            autocomplete="organization"
            required
          />
        </label>

        <label class="field">
          <span class="field-label">Email</span>
          <input
            v-model="email"
            type="email"
            class="field-input"
            placeholder="you@company.com"
            autocomplete="email"
            required
          />
        </label>

        <label class="field">
          <span class="field-label">Password</span>
          <input
            v-model="password"
            type="password"
            class="field-input"
            autocomplete="current-password"
            required
          />
        </label>

        <p v-if="auth.loginError" class="form-error" role="alert">
          {{ auth.loginError }}
        </p>

        <button type="submit" class="submit-button" :disabled="auth.isLoggingIn">
          {{ auth.isLoggingIn ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>
    </main>
  </div>
</template>

<style scoped>
.login-screen {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
}

@media (max-width: 860px) {
  .login-screen {
    grid-template-columns: 1fr;
  }
}

/* --- Brand panel --- */
.brand-panel {
  background: var(--color-ink);
  color: var(--color-paper);
  padding: 4rem 3.5rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

@media (max-width: 860px) {
  .brand-panel {
    padding: 3rem 2rem;
    min-height: 260px;
  }
}

.brand-mark {
  font-family: var(--font-display);
  font-weight: 800;
  font-size: 2rem;
  letter-spacing: -0.02em;
}

.brand-tagline {
  margin-top: 0.75rem;
  max-width: 26ch;
  color: #b9c2cb;
  font-size: 1rem;
  line-height: 1.5;
}

.roster {
  margin-top: 3.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  max-width: 320px;
}

.roster-bar {
  height: 10px;
  border-radius: 3px;
  background: rgba(255, 255, 255, 0.08);
  position: relative;
  overflow: hidden;
}

.roster-bar::after {
  content: '';
  position: absolute;
  inset: 0;
  width: var(--w);
  border-radius: 3px;
  background: linear-gradient(90deg, var(--color-amber), var(--color-amber-dark));
  transform: scaleX(0);
  transform-origin: left;
  animation: fill-bar 0.7s ease-out forwards;
  animation-delay: var(--delay);
}

@keyframes fill-bar {
  to {
    transform: scaleX(1);
  }
}

/* --- Form panel --- */
.form-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.login-form {
  width: 100%;
  max-width: 360px;
}

.form-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.65rem;
  margin: 0 0 0.4rem;
}

.form-subtitle {
  color: var(--color-slate);
  font-size: 0.925rem;
  margin: 0 0 2rem;
}

.field {
  display: block;
  margin-bottom: 1.25rem;
}

.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-slate);
  margin-bottom: 0.4rem;
}

.field-input {
  width: 100%;
  padding: 0.65rem 0.8rem;
  font-size: 0.95rem;
  font-family: var(--font-body);
  color: var(--color-ink);
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.field-input--mono {
  font-family: var(--font-mono);
  letter-spacing: 0.02em;
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
}

.form-error {
  color: var(--color-danger);
  font-size: 0.875rem;
  margin: -0.5rem 0 1.25rem;
}

.submit-button {
  width: 100%;
  padding: 0.75rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s ease;
}

.submit-button:hover:not(:disabled) {
  background: var(--color-amber-dark);
}

.submit-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.submit-button:focus-visible {
  outline: 2px solid var(--color-ink);
  outline-offset: 2px;
}
</style>
