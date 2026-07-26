<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { ApiError, type ValidationErrors } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

import type { LoginCredentials } from '@/types/auth'

const router = useRouter()
const authStore = useAuthStore()

const credentials = reactive<LoginCredentials>({
  email: '',
  password: '',
  device_name: 'shiftpilot-pwa',
})

const showPassword = ref(false)
const generalError = ref('')
const fieldErrors = ref<ValidationErrors>({})

function firstError(errors: ValidationErrors): string {
  return Object.values(errors).flat().find(Boolean) ?? ''
}

async function handleSubmit(): Promise<void> {
  generalError.value = ''
  fieldErrors.value = {}

  try {
    await authStore.login(credentials)

    await router.push({
      name: 'dashboard',
    })
  } catch (error: unknown) {
    if (error instanceof ApiError) {
      fieldErrors.value = error.errors

      generalError.value = firstError(error.errors) || error.message

      return
    }

    generalError.value = 'The server could not be reached. Please try again.'
  }
}
</script>

<template>
  <main class="login-page">
    <section class="brand-panel">
      <div class="brand-panel__content">
        <div class="brand-mark">
          <img src="/shiftpilot-icon.svg" alt="" width="72" height="72" />

          <span>ShiftPilot</span>
        </div>

        <div class="brand-message">
          <p class="eyebrow">Workforce operations</p>

          <h1>
            The right people.
            <br />
            The right shift.
          </h1>

          <p>
            Plan availability, qualifications, locations and assignments in one reliable workspace.
          </p>
        </div>

        <p class="brand-panel__footer">Scheduling made clear.</p>
      </div>
    </section>

    <section class="login-panel">
      <div class="login-card">
        <header class="login-card__header">
          <div class="mobile-brand">
            <img src="/shiftpilot-icon.svg" alt="" width="48" height="48" />

            <span>ShiftPilot</span>
          </div>

          <p class="eyebrow">Welcome back</p>

          <h2>Sign in to your account</h2>

          <p>Enter your company account details to continue.</p>
        </header>

        <form class="login-form" novalidate @submit.prevent="handleSubmit">
          <div v-if="generalError" class="form-alert" role="alert">
            {{ generalError }}
          </div>

          <div class="form-field">
            <label for="email"> Email address </label>

            <input
              id="email"
              v-model.trim="credentials.email"
              name="email"
              type="email"
              autocomplete="email"
              placeholder="name@company.com"
              :aria-invalid="Boolean(fieldErrors.email)"
              :aria-describedby="fieldErrors.email ? 'email-error' : undefined"
              required
            />

            <p v-if="fieldErrors.email" id="email-error" class="field-error">
              {{ fieldErrors.email[0] }}
            </p>
          </div>

          <div class="form-field">
            <div class="field-heading">
              <label for="password"> Password </label>

              <button class="password-toggle" type="button" @click="showPassword = !showPassword">
                {{ showPassword ? 'Hide password' : 'Show password' }}
              </button>
            </div>

            <input
              id="password"
              v-model="credentials.password"
              name="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              placeholder="Enter your password"
              :aria-invalid="Boolean(fieldErrors.password)"
              :aria-describedby="fieldErrors.password ? 'password-error' : undefined"
              required
            />

            <p v-if="fieldErrors.password" id="password-error" class="field-error">
              {{ fieldErrors.password[0] }}
            </p>
          </div>

          <button class="submit-button" type="submit" :disabled="authStore.isLoading">
            <span v-if="authStore.isLoading"> Signing in… </span>

            <span v-else> Sign in </span>
          </button>
        </form>

        <footer class="login-card__footer">
          <p>Need access? Contact your company administrator.</p>
        </footer>
      </div>
    </section>
  </main>
</template>

<style scoped>
.login-page {
  display: grid;
  min-height: 100vh;
  grid-template-columns:
    minmax(320px, 0.95fr)
    minmax(420px, 1.05fr);
}

.brand-panel {
  position: relative;
  overflow: hidden;

  color: #fff;
  background:
    radial-gradient(circle at 20% 10%, rgb(22 184 166 / 28%), transparent 32%),
    linear-gradient(150deg, var(--color-primary-950), var(--color-primary-800));
}

.brand-panel::after {
  position: absolute;
  right: -120px;
  bottom: -160px;

  width: 420px;
  height: 420px;

  content: '';
  border: 1px solid rgb(255 255 255 / 12%);
  border-radius: 50%;
}

.brand-panel__content {
  position: relative;
  z-index: 1;

  display: flex;
  width: min(100%, 620px);
  min-height: 100%;
  padding: clamp(32px, 6vw, 76px);
  margin-left: auto;

  flex-direction: column;
}

.brand-mark,
.mobile-brand {
  display: flex;
  align-items: center;
  gap: 14px;

  font-size: 1.25rem;
  font-weight: 750;
  letter-spacing: -0.02em;
}

.brand-mark img,
.mobile-brand img {
  border-radius: 18px;
  box-shadow: 0 12px 30px rgb(0 0 0 / 22%);
}

.brand-message {
  margin: auto 0;
}

.eyebrow {
  margin: 0 0 12px;

  color: var(--color-accent-500);

  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.brand-message h1 {
  max-width: 580px;
  margin: 0;

  font-size: clamp(2.7rem, 5vw, 5.3rem);
  line-height: 0.98;
  letter-spacing: -0.055em;
}

.brand-message > p:last-child {
  max-width: 510px;
  margin: 30px 0 0;

  color: rgb(255 255 255 / 72%);

  font-size: clamp(1rem, 1.5vw, 1.18rem);
  line-height: 1.75;
}

.brand-panel__footer {
  margin: 0;

  color: rgb(255 255 255 / 55%);
  font-size: 0.9rem;
}

.login-panel {
  display: grid;
  padding: clamp(24px, 5vw, 80px);

  place-items: center;
  background: linear-gradient(135deg, rgb(255 255 255 / 80%), rgb(244 247 251 / 96%));
}

.login-card {
  width: min(100%, 480px);
  padding: clamp(28px, 5vw, 48px);

  background: var(--color-surface);
  border: 1px solid rgb(220 228 238 / 80%);
  border-radius: var(--radius-large);
  box-shadow: var(--shadow-card);
}

.mobile-brand {
  display: none;
  margin-bottom: 40px;

  color: var(--color-primary-900);
}

.login-card__header h2 {
  margin: 0;

  color: var(--color-primary-950);

  font-size: clamp(1.8rem, 4vw, 2.35rem);
  line-height: 1.12;
  letter-spacing: -0.04em;
}

.login-card__header > p:last-child {
  margin: 16px 0 0;

  color: var(--color-text-muted);
}

.login-form {
  display: grid;
  margin-top: 34px;
  gap: 22px;
}

.form-alert {
  padding: 13px 15px;

  color: var(--color-danger);
  background: var(--color-danger-soft);
  border: 1px solid rgb(201 54 79 / 18%);
  border-radius: var(--radius-small);

  font-size: 0.9rem;
}

.form-field {
  display: grid;
  gap: 8px;
}

.field-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.form-field label {
  color: var(--color-primary-950);

  font-size: 0.9rem;
  font-weight: 700;
}

.form-field input {
  width: 100%;
  min-height: 52px;
  padding: 0 15px;

  color: var(--color-text);
  background: #fff;

  border: 1px solid var(--color-border);
  border-radius: var(--radius-small);
  outline: none;

  transition:
    border-color 150ms ease,
    box-shadow 150ms ease;
}

.form-field input::placeholder {
  color: #98a4b5;
}

.form-field input:focus {
  border-color: var(--color-primary-600);
  box-shadow: 0 0 0 4px rgb(47 117 181 / 12%);
}

.form-field input[aria-invalid='true'] {
  border-color: var(--color-danger);
}

.password-toggle {
  padding: 0;

  color: var(--color-primary-600);
  background: transparent;
  border: 0;

  font-size: 0.82rem;
  font-weight: 700;
}

.password-toggle:hover {
  color: var(--color-primary-900);
}

.field-error {
  margin: 0;

  color: var(--color-danger);
  font-size: 0.82rem;
}

.submit-button {
  display: inline-flex;
  min-height: 54px;
  align-items: center;
  justify-content: center;
  margin-top: 4px;

  color: #fff;
  background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-800));

  border: 0;
  border-radius: var(--radius-small);
  box-shadow: 0 12px 24px rgb(47 117 181 / 22%);

  font-weight: 800;

  transition:
    transform 150ms ease,
    box-shadow 150ms ease,
    opacity 150ms ease;
}

.submit-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 16px 28px rgb(47 117 181 / 28%);
}

.submit-button:disabled {
  cursor: wait;
  opacity: 0.65;
}

.login-card__footer {
  padding-top: 24px;
  margin-top: 28px;

  color: var(--color-text-muted);
  border-top: 1px solid var(--color-border);

  font-size: 0.84rem;
  text-align: center;
}

.login-card__footer p {
  margin: 0;
}

@media (max-width: 840px) {
  .login-page {
    grid-template-columns: 1fr;
  }

  .brand-panel {
    display: none;
  }

  .login-panel {
    min-height: 100vh;
    padding: max(22px, env(safe-area-inset-top)) 18px max(22px, env(safe-area-inset-bottom));
  }

  .login-card {
    padding: 28px 22px;
  }

  .mobile-brand {
    display: flex;
  }
}

@media (max-width: 420px) {
  .login-card {
    border-radius: 22px;
  }

  .field-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 4px;
  }
}
</style>
