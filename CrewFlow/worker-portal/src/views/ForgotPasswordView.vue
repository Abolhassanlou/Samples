<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { requestPasswordReset } from '@/api/passwordReset'

const companyCode = ref('')
const email = ref('')
const submitting = ref(false)
const submitted = ref(false)
const errorMessage = ref('')

async function handleSubmit() {
  errorMessage.value = ''
  submitting.value = true
  try {
    await requestPasswordReset(companyCode.value.trim().toLowerCase(), email.value.trim())
    submitted.value = true
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Something went wrong. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="screen">
    <div class="card">
      <div class="brand-mark">CrewFlow</div>
      <h1 class="title">Reset your password</h1>

      <template v-if="submitted">
        <p class="lead">
          If an account with that email exists, a reset link has been sent — check your inbox.
        </p>
        <RouterLink to="/login" class="back-link">Back to sign in</RouterLink>
      </template>

      <template v-else>
        <p class="lead">Enter your company and email — we'll send you a link to set a new password.</p>

        <form class="form" @submit.prevent="handleSubmit">
          <label class="field">
            <span class="field-label">Company code</span>
            <input v-model="companyCode" type="text" class="field-input field-input--mono" placeholder="acme2024" required />
          </label>

          <label class="field">
            <span class="field-label">Email</span>
            <input v-model="email" type="email" class="field-input" required />
          </label>

          <p v-if="errorMessage" class="error-banner" role="alert">{{ errorMessage }}</p>

          <button type="submit" class="submit-button" :disabled="submitting">
            {{ submitting ? 'Sending…' : 'Send reset link' }}
          </button>
        </form>

        <RouterLink to="/login" class="back-link">Back to sign in</RouterLink>
      </template>
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
  line-height: 1.5;
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

.back-link {
  display: block;
  text-align: center;
  margin-top: 1.25rem;
  font-size: 0.85rem;
  color: var(--color-amber-dark);
  font-weight: 600;
  text-decoration: none;
}
</style>
