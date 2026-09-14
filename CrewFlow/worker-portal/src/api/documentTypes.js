import client from '@/api/client'

/**
 * Merges the fixed baseline (WorkerDocumentController::types()) with any
 * active company-added types (CustomDocumentTypeController::index()),
 * filtered to one category — "personal" (shown under My info) or "work"
 * (shown under the top-level Documents section). See the Employee
 * module's README for why these are two separate lists.
 */
export async function fetchDocumentTypes(category) {
  const [fixed, custom] = await Promise.all([
    client.get('documents/types', { params: { category } }).then((r) => r.data.data),
    client.get('custom-document-types', { params: { category } }).then((r) => r.data.data),
  ])

  return [...fixed, ...custom.map((t) => ({ key: t.key, label: t.label, category: t.category }))]
}
