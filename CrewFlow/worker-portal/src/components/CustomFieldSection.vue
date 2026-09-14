<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { fetchCustomFields, fetchAnswers, saveAnswers } from '@/api/customFields'
import CustomFieldForm from '@/components/CustomFieldForm.vue'

const props = defineProps({
  category: { type: String, required: true }, // "personal_info" | "skill"
})

const auth = useAuthStore()

const fields = ref([])
const answers = ref([])
const loading = ref(true)
const savedNote = ref(false)

async function loadAll() {
  loading.value = true
  const [f, a] = await Promise.all([fetchCustomFields(props.category), fetchAnswers(auth.user.id)])
  fields.value = f
  answers.value = a
  loading.value = false
}

onMounted(loadAll)

async function handleSave(payload) {
  answers.value = await saveAnswers(auth.user.id, payload)
  savedNote.value = true
  setTimeout(() => (savedNote.value = false), 2000)
}
</script>

<template>
  <div>
    <p v-if="loading" class="loading-note">Loading…</p>
    <template v-else>
      <CustomFieldForm :fields="fields" :answers="answers" @save="handleSave" />
      <p v-if="savedNote" class="saved-note">Saved.</p>
    </template>
  </div>
</template>

<style scoped>
.loading-note {
  font-size: 0.82rem;
  color: var(--color-slate);
  margin: 0;
}

.saved-note {
  font-size: 0.78rem;
  color: var(--color-green);
  margin: 0.5rem 0 0;
}
</style>
