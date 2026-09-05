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
  <div class="screen">
    <div class="card">
      <div class="brand-mark">CrewFlow</div>
      <h1 class="title">Sign in</h1>
      <p class="lead">Enter your company's workspace to continue.</p>

      <form class="form" @submit.prevent="handleSubmit">
        <label class="field">
          <span class="field-label">Company code</span>
          <input v-model="companyCode" type="text" class="field-input field-input--mono" placeholder="acme2024" required />
        </label>

        <label class="field">
          <span class="field-label">Email</span>
          <input v-model="email" type="email" class="field-input" required />
        </label>

        <label class="field">
          <span class="field-label">Password</span>
          <input v-model="password" type="password" class="field-input" required />
        </label>

        <p v-if="auth.loginError" class="error-banner" role="alert">{{ auth.loginError }}</p>

        <button type="submit" class="submit-button" :disabled="auth.isLoggingIn">
          {{ auth.isLoggingIn ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.screen {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: var(--color-ink);
}

.card {
  width: 100%;
  max-width: 380px;
  background: var(--color-paper);
  border-radius: 14px;
  padding: 2rem 1.75rem;
}

.brand-mark {
  font-family: var(--font-display);
  font-weight: 800;
  font-size: 1.3rem;
  margin-bottom: 1.5rem;
}

.title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.4rem;
  margin: 0 0 0.4rem;
}

.lead {
  font-size: 0.9rem;
  color: var(--color-slate);
  margin: 0 0 1.5rem;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.field {
  display: block;
}

.field-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-slate);
  margin-bottom: 0.35rem;
}

.field-input {
  width: 100%;
  padding: 0.7rem 0.85rem;
  font-size: 1rem;
  font-family: var(--font-body);
  border: 1px solid var(--color-line);
  border-radius: 8px;
  outline: none;
  background: #fff;
}

.field-input--mono {
  font-family: var(--font-mono);
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
}

.error-banner {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.7rem 0.9rem;
  font-size: 0.85rem;
  margin: 0;
}

.submit-button {
  padding: 0.8rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.submit-button:hover:not(:disabled) {
  background: var(--color-amber-dark);
}

.submit-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
