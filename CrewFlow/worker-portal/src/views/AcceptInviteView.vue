<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { fetchInvitation, acceptInvitation } from '@/api/invitations'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const companyCode = route.query.company
const token = route.query.token

const loading = ref(true)
const loadError = ref('')
const invitation = ref(null)

const name = ref('')
const phone = ref('')
const password = ref('')
const passwordConfirm = ref('')
const submitting = ref(false)
const submitError = ref('')

onMounted(async () => {
  if (!companyCode || !token) {
    loadError.value = 'This invitation link is missing information and cannot be used.'
    loading.value = false
    return
  }

  try {
    invitation.value = await fetchInvitation(companyCode, token)
  } catch {
    loadError.value = 'This invitation link is invalid or has expired. Ask your employer to send a new one.'
  } finally {
    loading.value = false
  }
})

async function handleSubmit() {
  submitError.value = ''

  if (password.value !== passwordConfirm.value) {
    submitError.value = "Passwords don't match."
    return
  }

  submitting.value = true
  try {
    const result = await acceptInvitation(companyCode, token, {
      name: name.value.trim(),
      phone: phone.value.trim(),
      password: password.value,
    })

    auth.setSession({ companyCode, token: result.token, user: result.user })
    router.push('/')
  } catch (error) {
    submitError.value =
      error.response?.data?.message || 'Something went wrong. Please check the details and try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="screen">
    <div class="card">
      <div class="brand-mark">CrewFlow</div>

      <p v-if="loading" class="loading-note">Checking your invitation…</p>

      <p v-else-if="loadError" class="error-banner" role="alert">{{ loadError }}</p>

      <template v-else>
        <h1 class="title">You're invited</h1>
        <p class="lead">
          Join <strong>{{ invitation.company_name }}</strong> as
          <strong>{{ invitation.email }}</strong
          >. Set up your account to get started.
        </p>

        <form class="form" @submit.prevent="handleSubmit">
          <label class="field">
            <span class="field-label">Full name</span>
            <input v-model="name" type="text" class="field-input" required />
          </label>

          <label class="field">
            <span class="field-label">Phone</span>
            <input v-model="phone" type="tel" class="field-input" required />
          </label>

          <label class="field">
            <span class="field-label">Password</span>
            <input v-model="password" type="password" class="field-input" minlength="8" required />
          </label>

          <label class="field">
            <span class="field-label">Confirm password</span>
            <input v-model="passwordConfirm" type="password" class="field-input" minlength="8" required />
          </label>

          <p v-if="submitError" class="error-banner" role="alert">{{ submitError }}</p>

          <button type="submit" class="submit-button" :disabled="submitting">
            {{ submitting ? 'Setting up…' : 'Set up my account' }}
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
  max-width: 400px;
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

.loading-note {
  color: var(--color-slate);
  font-size: 0.9rem;
}

.title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.4rem;
  margin: 0 0 0.6rem;
}

.lead {
  font-size: 0.9rem;
  color: var(--color-slate);
  line-height: 1.5;
  margin: 0 0 1.5rem;
}

.lead strong {
  color: var(--color-ink);
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
