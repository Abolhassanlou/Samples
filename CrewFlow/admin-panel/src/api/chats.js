import client from '@/api/client'

/**
 * inboxOnly=true switches to the admin-facing "who's written to me"
 * view — filtered to conversations the other participant has actually
 * replied in, previewed/sorted by their message, not the admin's own
 * last word. See the Chat module's README, "Admin inbox mode", for why.
 */
export function fetchConversations(inboxOnly = false) {
  return client.get('chats', { params: inboxOnly ? { inbox_only: 1 } : {} }).then((r) => r.data.data)
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
