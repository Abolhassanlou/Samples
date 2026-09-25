<script setup>
import { ref, onMounted, computed, nextTick, watch } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useAuthStore } from '@/stores/auth'
import { fetchConversations, fetchMessages, sendMessage, sendBroadcast } from '@/api/chats'
import { fetchWorkers, fetchBranches, fetchQualifications } from '@/api/workers'

const auth = useAuthStore()

const conversations = ref([])
const loading = ref(true)
const loadError = ref('')

// 'empty' (nothing selected) | 'thread' (viewing/replying to one
// conversation) | 'compose' (the new-broadcast form)
const mode = ref('empty')

const activeConversation = ref(null)
const messages = ref([])
const loadingMessages = ref(false)
const messageDraft = ref('')
const sending = ref(false)
const threadError = ref('')
const messagesEnd = ref(null)

// Broadcast composer state
const workers = ref([])
const loadingWorkers = ref(false)
const selectedWorkerIds = ref([])
const broadcastMessage = ref('')

// Narrows the recipient checklist below — same filters WorkersView
// already offers, reused here via the same fetchWorkers(filters) call
// rather than duplicating the logic client-side.
const branches = ref([])
const qualifications = ref([])
const recipientSearch = ref('')
const recipientBranchId = ref('')
const recipientQualificationId = ref('')
const recipientWorkTimeModel = ref('')
const recipientNightShift = ref(false)
const recipientEligibleOnly = ref(false)
const broadcasting = ref(false)
const broadcastError = ref('')
const broadcastSentNote = ref('')

async function loadConversations() {
  loading.value = true
  loadError.value = ''
  try {
    conversations.value = await fetchConversations()
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Could not load conversations. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadConversations)

function conversationTitle(conversation) {
  if (conversation.title) return conversation.title
  const other = conversation.participants?.find((p) => p.id !== auth.user.id)
  return other?.name || 'Conversation'
}

async function openConversation(conversation) {
  mode.value = 'thread'
  activeConversation.value = conversation
  loadingMessages.value = true
  threadError.value = ''
  try {
    messages.value = await fetchMessages(conversation.id)
    await nextTick()
    scrollToBottom()
  } finally {
    loadingMessages.value = false
  }
}

function scrollToBottom() {
  messagesEnd.value?.scrollIntoView({ block: 'end' })
}

async function handleSend() {
  const text = messageDraft.value.trim()
  if (!text) return

  sending.value = true
  threadError.value = ''
  try {
    const sent = await sendMessage(activeConversation.value.id, text)
    messages.value.push(sent)
    messageDraft.value = ''
    await nextTick()
    scrollToBottom()
    loadConversations()
  } catch (error) {
    threadError.value = error.response?.data?.message || 'Could not send that. Please try again.'
  } finally {
    sending.value = false
  }
}

async function loadRecipientWorkers() {
  loadingWorkers.value = true
  try {
    workers.value = await fetchWorkers({
      search: recipientSearch.value,
      branchId: recipientBranchId.value,
      qualificationId: recipientQualificationId.value,
      workTimeModel: recipientWorkTimeModel.value,
      nightShift: recipientNightShift.value,
      eligibleOnly: recipientEligibleOnly.value,
    })
    // A filter change can drop someone who was checked — keep only
    // selections still present in the narrowed list, rather than
    // silently broadcasting to someone no longer shown.
    const stillVisible = new Set(workers.value.map((w) => w.user_id))
    selectedWorkerIds.value = selectedWorkerIds.value.filter((id) => stillVisible.has(id))
  } catch {
    broadcastError.value = 'Could not load the worker list.'
  } finally {
    loadingWorkers.value = false
  }
}

watch(
  [recipientSearch, recipientBranchId, recipientQualificationId, recipientWorkTimeModel, recipientNightShift, recipientEligibleOnly],
  () => {
    if (mode.value === 'compose') loadRecipientWorkers()
  },
)

async function openComposer() {
  mode.value = 'compose'
  activeConversation.value = null
  broadcastError.value = ''
  broadcastSentNote.value = ''
  selectedWorkerIds.value = []
  broadcastMessage.value = ''
  recipientSearch.value = ''
  recipientBranchId.value = ''
  recipientQualificationId.value = ''
  recipientWorkTimeModel.value = ''
  recipientNightShift.value = false
  recipientEligibleOnly.value = false

  if (branches.value.length === 0) {
    try {
      branches.value = await fetchBranches()
    } catch {
      // Filter list is a nice-to-have — the plain recipient checklist
      // still works even if this fails, so no error surfaced here.
    }
  }
  if (qualifications.value.length === 0) {
    try {
      qualifications.value = await fetchQualifications()
    } catch {
      // Same reasoning as branches above.
    }
  }

  await loadRecipientWorkers()
}

async function handleBroadcast() {
  if (selectedWorkerIds.value.length === 0 || !broadcastMessage.value.trim()) return

  broadcasting.value = true
  broadcastError.value = ''
  try {
    await sendBroadcast(selectedWorkerIds.value, broadcastMessage.value.trim())
    broadcastSentNote.value = `Sent to ${selectedWorkerIds.value.length} worker${selectedWorkerIds.value.length === 1 ? '' : 's'}.`
    selectedWorkerIds.value = []
    broadcastMessage.value = ''
    loadConversations()
  } catch (error) {
    broadcastError.value = error.response?.data?.message || 'Could not send this broadcast.'
  } finally {
    broadcasting.value = false
  }
}

function toggleWorker(userId) {
  const idx = selectedWorkerIds.value.indexOf(userId)
  if (idx === -1) selectedWorkerIds.value.push(userId)
  else selectedWorkerIds.value.splice(idx, 1)
}

const allWorkersSelected = computed(
  () => workers.value.length > 0 && selectedWorkerIds.value.length === workers.value.length
)

function toggleSelectAll() {
  selectedWorkerIds.value = allWorkersSelected.value ? [] : workers.value.map((w) => w.user_id)
}

function formatTime(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

function formatConversationTime(dateString) {
  if (!dateString) return ''
  const d = new Date(dateString)
  const isToday = d.toDateString() === new Date().toDateString()
  return isToday
    ? d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
    : d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}
</script>

<template>
  <AppShell>
    <div class="chat-layout">
      <aside class="sidebar">
        <div class="sidebar-header">
          <h1 class="page-title">Chat</h1>
          <button class="new-broadcast-button" @click="openComposer">+ New broadcast</button>
        </div>

        <p v-if="loading" class="loading-note">Loading…</p>
        <p v-else-if="loadError" class="error-banner" role="alert">{{ loadError }}</p>

        <div v-else-if="conversations.length > 0" class="conversation-list">
          <button
            v-for="conversation in conversations"
            :key="conversation.id"
            class="conversation-row"
            :class="{ 'conversation-row--active': activeConversation?.id === conversation.id }"
            @click="openConversation(conversation)"
          >
            <div class="conversation-row-top">
              <span class="conversation-title">{{ conversationTitle(conversation) }}</span>
              <span class="conversation-time">{{ formatConversationTime(conversation.created_at) }}</span>
            </div>
            <span v-if="conversation.last_message" class="conversation-preview">{{ conversation.last_message }}</span>
            <span v-else class="conversation-preview conversation-preview--empty">No messages yet</span>
          </button>
        </div>
        <p v-else class="empty-note">No conversations yet — start a broadcast to reach your workers.</p>
      </aside>

      <main class="main-panel">
        <!-- Empty state -->
        <div v-if="mode === 'empty'" class="empty-state">
          <p>Select a conversation, or start a new broadcast.</p>
        </div>

        <!-- Thread view -->
        <template v-else-if="mode === 'thread'">
          <header class="thread-header">
            <h2 class="thread-title">{{ conversationTitle(activeConversation) }}</h2>
          </header>

          <div class="thread-body">
            <p v-if="loadingMessages" class="loading-note">Loading…</p>
            <div v-else class="message-list">
              <div
                v-for="message in messages"
                :key="message.id"
                class="message-bubble"
                :class="{ 'message-bubble--mine': message.sender_id === auth.user.id }"
              >
                <span v-if="message.sender_id !== auth.user.id" class="message-sender">{{ message.sender_name }}</span>
                <p class="message-text">{{ message.message }}</p>
                <span class="message-time">{{ formatTime(message.created_at) }}</span>
              </div>
              <div ref="messagesEnd"></div>
            </div>
          </div>

          <p v-if="threadError" class="error-banner error-banner--inline" role="alert">{{ threadError }}</p>

          <form class="composer" @submit.prevent="handleSend">
            <input
              v-model="messageDraft"
              type="text"
              class="composer-input"
              placeholder="Type a message…"
              :disabled="sending"
            />
            <button type="submit" class="send-button" :disabled="sending || !messageDraft.trim()">Send</button>
          </form>
        </template>

        <!-- Broadcast composer -->
        <template v-else-if="mode === 'compose'">
          <header class="thread-header">
            <h2 class="thread-title">New broadcast</h2>
            <p class="thread-sublead">
              Sends one message to each selected worker as their own private reply thread with
              you — they won't see each other's replies.
            </p>
          </header>

          <div class="compose-body">
            <div class="recipient-filters">
              <input v-model="recipientSearch" type="search" class="filter-input" placeholder="Search name, email, #…" />

              <select v-model="recipientBranchId" class="filter-input">
                <option value="">Any branch</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>

              <select v-model="recipientQualificationId" class="filter-input">
                <option value="">Any qualification</option>
                <option v-for="q in qualifications" :key="q.id" :value="q.id">{{ q.name }}</option>
              </select>

              <select v-model="recipientWorkTimeModel" class="filter-input">
                <option value="">Any work time model</option>
                <option value="full_time">Vollzeit</option>
                <option value="part_time">Teilzeit</option>
                <option value="casual">Fallweise Beschäftigung</option>
              </select>

              <label class="filter-checkbox">
                <input type="checkbox" v-model="recipientNightShift" />
                Works night shifts
              </label>

              <label class="filter-checkbox">
                <input type="checkbox" v-model="recipientEligibleOnly" />
                Assignable right now
              </label>
            </div>

            <p v-if="loadingWorkers" class="loading-note">Loading workers…</p>
            <template v-else>
              <div class="recipient-header">
                <span class="field-label">{{ selectedWorkerIds.length }} selected · {{ workers.length }} shown</span>
                <button type="button" class="select-all-link" @click="toggleSelectAll">
                  {{ allWorkersSelected ? 'Deselect all' : 'Select all' }}
                </button>
              </div>
              <div class="recipient-list">
                <label v-for="worker in workers" :key="worker.user_id" class="recipient-row">
                  <input
                    type="checkbox"
                    :value="worker.user_id"
                    :checked="selectedWorkerIds.includes(worker.user_id)"
                    @change="toggleWorker(worker.user_id)"
                  />
                  {{ worker.name }}
                </label>
                <p v-if="workers.length === 0" class="empty-note">No workers found.</p>
              </div>

              <label class="field">
                <span class="field-label">Message</span>
                <textarea v-model="broadcastMessage" class="field-input" rows="4" placeholder="Type your message…"></textarea>
              </label>

              <p v-if="broadcastSentNote" class="sent-note">{{ broadcastSentNote }}</p>
              <p v-if="broadcastError" class="error-banner error-banner--inline" role="alert">{{ broadcastError }}</p>

              <button
                class="send-broadcast-button"
                :disabled="broadcasting || selectedWorkerIds.length === 0 || !broadcastMessage.trim()"
                @click="handleBroadcast"
              >
                {{ broadcasting ? 'Sending…' : `Send to ${selectedWorkerIds.length} worker${selectedWorkerIds.length === 1 ? '' : 's'}` }}
              </button>
            </template>
          </div>
        </template>
      </main>
    </div>
  </AppShell>
</template>

<style scoped>
.chat-layout {
  display: flex;
  height: calc(100vh - 64px);
  border: 1px solid var(--color-line);
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
}

.sidebar {
  width: 320px;
  flex-shrink: 0;
  border-right: 1px solid var(--color-line);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

.sidebar-header {
  padding: 1.1rem 1.1rem 0.9rem;
  border-bottom: 1px solid var(--color-line);
}

.page-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.05rem;
  margin: 0 0 0.7rem;
}

.new-broadcast-button {
  width: 100%;
  padding: 0.55rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.loading-note {
  padding: 1rem 1.1rem;
  color: var(--color-slate);
  font-size: 0.85rem;
}

.error-banner {
  margin: 0.75rem 1.1rem;
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.82rem;
}

.error-banner--inline {
  margin: 0.75rem 0;
}

.empty-note {
  padding: 1rem 1.1rem;
  color: var(--color-slate);
  font-style: italic;
  font-size: 0.82rem;
}

.conversation-list {
  display: flex;
  flex-direction: column;
}

.conversation-row {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  border-bottom: 1px solid var(--color-line);
  padding: 0.75rem 1.1rem;
  font-family: inherit;
  cursor: pointer;
}

.conversation-row--active {
  background: rgba(224, 151, 58, 0.1);
}

.conversation-row-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: 0.5rem;
}

.conversation-title {
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--color-ink);
}

.conversation-time {
  font-size: 0.7rem;
  color: var(--color-slate);
  flex-shrink: 0;
}

.conversation-preview {
  font-size: 0.78rem;
  color: var(--color-slate);
  margin-top: 0.15rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.conversation-preview--empty {
  font-style: italic;
}

.main-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.empty-state {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-slate);
  font-size: 0.9rem;
}

.thread-header {
  padding: 1.1rem 1.5rem;
  border-bottom: 1px solid var(--color-line);
}

.thread-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1rem;
  margin: 0;
}

.thread-sublead {
  font-size: 0.82rem;
  color: var(--color-slate);
  margin: 0.4rem 0 0;
  line-height: 1.5;
}

.thread-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem 1.5rem;
}

.message-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.message-bubble {
  align-self: flex-start;
  max-width: 60%;
  background: var(--color-paper);
  border: 1px solid var(--color-line);
  border-radius: 12px 12px 12px 2px;
  padding: 0.5rem 0.75rem;
}

.message-bubble--mine {
  align-self: flex-end;
  background: rgba(224, 151, 58, 0.16);
  border-color: rgba(224, 151, 58, 0.3);
  border-radius: 12px 12px 2px 12px;
}

.message-sender {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-amber-dark);
  margin-bottom: 0.15rem;
}

.message-text {
  margin: 0;
  font-size: 0.86rem;
  color: var(--color-ink);
  white-space: pre-wrap;
  overflow-wrap: break-word;
}

.message-time {
  display: block;
  font-size: 0.68rem;
  color: var(--color-slate);
  margin-top: 0.2rem;
  text-align: right;
}

.composer {
  display: flex;
  gap: 0.6rem;
  padding: 0.9rem 1.5rem;
  border-top: 1px solid var(--color-line);
}

.composer-input {
  flex: 1;
  padding: 0.55rem 0.85rem;
  font-size: 0.88rem;
  border: 1px solid var(--color-line);
  border-radius: 999px;
  font-family: inherit;
}

.composer-input:focus-visible {
  border-color: var(--color-amber);
  outline: none;
}

.send-button {
  flex-shrink: 0;
  padding: 0.55rem 1.1rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 999px;
  cursor: pointer;
}

.send-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.compose-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.recipient-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px dashed var(--color-line);
}

.recipient-filters .filter-input {
  flex: 1 1 160px;
  min-width: 140px;
  padding: 0.45rem 0.65rem;
  font-size: 0.82rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  font-family: inherit;
}

.filter-checkbox {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.82rem;
  color: var(--color-ink);
  white-space: nowrap;
}

.recipient-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.field-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-slate);
}

.select-all-link {
  background: none;
  border: none;
  padding: 0;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-amber-dark);
  cursor: pointer;
}

.recipient-list {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  max-height: 220px;
  overflow-y: auto;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  padding: 0.6rem 0.8rem;
  margin-bottom: 1.1rem;
}

.recipient-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.field {
  display: block;
  margin-bottom: 1.1rem;
}

.field-input {
  width: 100%;
  padding: 0.6rem 0.75rem;
  font-size: 0.88rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  font-family: inherit;
  resize: vertical;
  margin-top: 0.35rem;
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  outline: none;
}

.sent-note {
  font-size: 0.82rem;
  color: var(--color-green);
  margin: 0 0 0.9rem;
}

.send-broadcast-button {
  padding: 0.6rem 1.2rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.send-broadcast-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
