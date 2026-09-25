import client from '@/api/client'

export function fetchConversations() {
  return client.get('chats').then((r) => r.data.data)
}

export function fetchMessages(conversationId) {
  return client.get(`chats/${conversationId}/messages`).then((r) => r.data.data)
}

export function sendMessage(conversationId, message) {
  return client.post(`chats/${conversationId}/messages`, { message }).then((r) => r.data.data)
}

/**
 * Get-or-create a direct conversation with another user — used when a
 * worker has no existing thread yet and needs to start one (e.g. with
 * their dispatcher/admin).
 */
export function startDirectConversation(userId) {
  return client.post('chats/direct', { user_id: userId }).then((r) => r.data.data)
}
