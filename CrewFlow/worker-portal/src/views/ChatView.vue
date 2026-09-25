<script setup>
import { ref, onMounted, nextTick } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useAuthStore } from '@/stores/auth'
import { fetchConversations, fetchMessages, sendMessage } from '@/api/chats'

const auth = useAuthStore()

const conversations = ref([])
const loading = ref(true)
const loadError = ref('')

// Which conversation is open in the full thread view — null means the
// list is showing instead.
const activeConversation = ref(null)
const messages = ref([])
const loadingMessages = ref(false)
const messageDraft = ref('')
const sending = ref(false)
const sendError = ref('')
const messagesEnd = ref(null)

async function loadConversations() {
  loading.value = true
  loadError.value = ''
  try {
    conversations.value = await fetchConversations()
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Could not load your messages. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadConversations)

function conversationTitle(conversation) {
  if (conversation.title) return conversation.title
  // A direct conversation has no title of its own — show the other
  // participant's name instead.
  const other = conversation.participants?.find((p) => p.id !== auth.user.id)
  return other?.name || 'Conversation'
}

async function openConversation(conversation) {
  activeConversation.value = conversation
  loadingMessages.value = true
  sendError.value = ''
  try {
    messages.value = await fetchMessages(conversation.id)
    await nextTick()
    scrollToBottom()
  } finally {
    loadingMessages.value = false
  }
}

function closeConversation() {
  activeConversation.value = null
  messages.value = []
}

function scrollToBottom() {
  messagesEnd.value?.scrollIntoView({ block: 'end' })
}

async function handleSend() {
  const text = messageDraft.value.trim()
  if (!text) return

  sending.value = true
  sendError.value = ''
  try {
    const sent = await sendMessage(activeConversation.value.id, text)
    messages.value.push(sent)
    messageDraft.value = ''
    await nextTick()
    scrollToBottom()
    // Refresh the list in the background so its last-message preview
    // and ordering stay current for next time the worker sees it.
    loadConversations()
  } catch (error) {
    sendError.value = error.response?.data?.message || 'Could not send that. Please try again.'
  } finally {
    sending.value = false
  }
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
    <!-- LIST VIEW -->
    <template v-if="!activeConversation">
      <header class="header">
        <h1 class="title">Chat</h1>
      </header>

      <p v-if="loading" class="loading-note">Loading…</p>
      <p v-else-if="loadError" class="error-banner" role="alert">{{ loadError }}</p>

      <div v-else-if="conversations.length > 0" class="conversation-list">
        <button
          v-for="conversation in conversations"
          :key="conversation.id"
          class="conversation-row"
          @click="openConversation(conversation)"
        >
          <span class="conversation-title">{{ conversationTitle(conversation) }}</span>
          <span v-if="conversation.last_message" class="conversation-preview">{{ conversation.last_message }}</span>
          <span v-else class="conversation-preview conversation-preview--empty">No messages yet</span>
        </button>
      </div>
      <p v-else class="empty-note">
        No messages yet — when your admin or dispatcher sends you something, it'll show up here.
      </p>
    </template>

    <!-- THREAD VIEW -->
    <template v-else>
      <header class="thread-header">
        <button class="back-button" @click="closeConversation">‹</button>
        <h1 class="title">{{ conversationTitle(activeConversation) }}</h1>
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

      <p v-if="sendError" class="error-banner error-banner--inline" role="alert">{{ sendError }}</p>

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
  </AppShell>
</template>

<style scoped>
.header {
  padding: 1.25rem 1.25rem 0.5rem;
}

.title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.3rem;
  margin: 0;
  color: var(--color-ink);
}

.loading-note {
  padding: 0 1.25rem;
  color: var(--color-slate);
  font-size: 0.9rem;
}

.error-banner {
  margin: 0 1.25rem;
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
}

.error-banner--inline {
  margin: 0.5rem 1.25rem;
}

.empty-note {
  padding: 0 1.25rem;
  color: var(--color-slate);
  font-style: italic;
  font-size: 0.85rem;
  line-height: 1.5;
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
  padding: 0.9rem 1.25rem;
  font-family: inherit;
  cursor: pointer;
}

.conversation-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-ink);
}

.conversation-preview {
  font-size: 0.82rem;
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

/* Thread view */
.thread-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 1.25rem 1.25rem 0.75rem;
  border-bottom: 1px solid var(--color-line);
}

.back-button {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid var(--color-line);
  background: #fff;
  font-size: 1.2rem;
  color: var(--color-ink);
  cursor: pointer;
}

.thread-body {
  padding: 1rem 1.25rem;
  min-height: 40vh;
}

.message-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.message-bubble {
  align-self: flex-start;
  max-width: 78%;
  background: #fff;
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
  font-size: 0.88rem;
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
  padding: 0.75rem 1.25rem calc(0.9rem + env(safe-area-inset-bottom));
  border-top: 1px solid var(--color-line);
  background: var(--color-paper);
}

.composer-input {
  flex: 1;
  padding: 0.6rem 0.85rem;
  font-size: 0.9rem;
  border: 1px solid var(--color-line);
  border-radius: 999px;
  background: #fff;
  font-family: inherit;
}

.composer-input:focus-visible {
  border-color: var(--color-amber);
  outline: none;
}

.send-button {
  flex-shrink: 0;
  padding: 0.6rem 1.1rem;
  font-size: 0.85rem;
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
</style>
