<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { resetPassword } from '@/api/passwordReset'

const route = useRoute()
const router = useRouter()

const companyCode = route.query.company
const token = route.query.token
const email = route.query.email

const password = ref('')
const passwordConfirm = ref('')
const submitting = ref(false)
const submitError = ref('')
const done = ref(false)

const missingParams = !companyCode || !token || !email

async function handleSubmit() {
  submitError.value = ''

  if (password.value !== passwordConfirm.value) {
    submitError.value = "Passwords don't match."
    return
  }

  submitting.value = true
  try {
    await resetPassword(companyCode, { email, token, password: password.value })
    done.value = true
  } catch (error) {
    submitError.value =
      error.response?.data?.message || 'This reset link is invalid or has expired. Request a new one.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="screen">
    <div class="card">
      <div class="brand-mark">CrewFlow</div>

      <p v-if="missingParams" class="error-banner" role="alert">
        This reset link is missing information and cannot be used. Request a new one from the
        sign-in page.
      </p>

      <template v-else-if="done">
        <h1 class="title">Password reset</h1>
        <p class="lead">You can sign in with your new password now.</p>
        <button class="submit-button" @click="router.push('/login')">Go to sign in</button>
      </template>

      <template v-else>
        <h1 class="title">Set a new password</h1>
        <p class="lead">for <strong>{{ email }}</strong></p>

        <form class="form" @submit.prevent="handleSubmit">
          <label class="field">
            <span class="field-label">New password</span>
            <input v-model="password" type="password" class="field-input" minlength="8" required />
          </label>

          <label class="field">
            <span class="field-label">Confirm new password</span>
            <input v-model="passwordConfirm" type="password" class="field-input" minlength="8" required />
          </label>

          <p v-if="submitError" class="error-banner" role="alert">{{ submitError }}</p>

          <button type="submit" class="submit-button" :disabled="submitting">
            {{ submitting ? 'Saving…' : 'Set new password' }}
          </button>
        </form>
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
  width: 100%;
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
