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

export function startDirectConversation(userId) {
  return client.post('chats/direct', { user_id: userId }).then((r) => r.data.data)
}

/**
 * One message fanned out to many recipients as separate private
 * threads — not a shared group. shifts.dispatch only (see the Chat
 * module's README).
 */
export function sendBroadcast(userIds, message) {
  return client.post('chats/broadcast', { user_ids: userIds, message }).then((r) => r.data.data)
}
