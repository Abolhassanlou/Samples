import client from '@/api/client'

export function fetchConversations() {
  return client.get('chats').then((r) => r.data.data)
}

/**
 * includeArchived=true also returns broadcast-origin messages older
 * than 30 days, which are left out by default — see the Chat module's
 * README, "30-day rolling archive", for why. A message either side
 * typed directly is never affected by this either way.
 */
export function fetchMessages(conversationId, includeArchived = false) {
  return client
    .get(`chats/${conversationId}/messages`, { params: includeArchived ? { include_archived: 1 } : {} })
    .then((r) => r.data.data)
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
