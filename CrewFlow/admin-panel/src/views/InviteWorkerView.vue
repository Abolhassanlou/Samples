<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'
import { inviteWorker } from '@/api/workers'

const email = ref('')
const submitting = ref(false)
const errorMessage = ref('')
const sentTo = ref('')

async function handleSubmit() {
  errorMessage.value = ''
  submitting.value = true
  try {
    await inviteWorker(email.value.trim())
    sentTo.value = email.value.trim()
    email.value = ''
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || 'Could not send this invitation. Check the email and try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AppShell>
    <h1 class="page-title">Invite a worker</h1>
    <p class="page-lead">
      Just their email — they'll get a link to set their own name, phone, and password. Once
      they've accepted, come back to their profile to set up a contract.
    </p>

    <div class="panel">
      <p v-if="sentTo" class="success-banner">
        Invitation sent to <strong>{{ sentTo }}</strong
        >. Ask them to check their inbox (or, for local testing, check
        <code>storage/logs/laravel.log</code> on the backend).
      </p>

      <p v-if="errorMessage" class="error-banner" role="alert">{{ errorMessage }}</p>

      <form @submit.prevent="handleSubmit">
        <label class="field">
          <span class="field-label">Email</span>
          <input v-model="email" type="email" class="field-input" required placeholder="worker@example.com" />
        </label>

        <button type="submit" class="submit-button" :disabled="submitting">
          {{ submitting ? 'Sending…' : 'Send invitation' }}
        </button>
      </form>

      <p class="alt-link">
        Importing existing employee data instead?
        <RouterLink to="/workers/new">Add a worker manually</RouterLink>.
      </p>
    </div>
  </AppShell>
</template>

<style scoped>
.page-title {
  font-family: var(--font-display);
  font-weight: 700;
  margin: 0 0 0.35rem;
}

.page-lead {
  color: var(--color-slate);
  margin: 0 0 1.5rem;
  max-width: 60ch;
}

.panel {
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 1.75rem;
  max-width: 480px;
}

.success-banner {
  color: var(--color-green);
  background: rgba(76, 139, 108, 0.1);
  border: 1px solid rgba(76, 139, 108, 0.3);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
  margin: 0 0 1.25rem;
}

.success-banner code {
  font-family: var(--font-mono);
  font-size: 0.8rem;
}

.error-banner {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
  margin: 0 0 1.25rem;
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
  padding: 0.6rem 0.8rem;
  font-size: 0.9rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  outline: none;
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
}

.submit-button {
  width: 100%;
  padding: 0.65rem;
  font-size: 0.9rem;
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

.alt-link {
  margin: 1.25rem 0 0;
  font-size: 0.8rem;
  color: var(--color-slate);
  text-align: center;
}

.alt-link a {
  color: var(--color-amber-dark);
  font-weight: 600;
}
</style>
