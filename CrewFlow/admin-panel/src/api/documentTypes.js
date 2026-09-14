import { fetchFixedDocumentTypes, fetchCustomDocumentTypes } from '@/api/customFields'

/**
 * Merges the fixed baseline with any active company-added types for one
 * category ("personal" or "work") — same helper as worker-portal's
 * api/documentTypes.js, duplicated here since these are separate
 * frontend projects. Used to know which document_type keys belong to
 * which category, so a worker's flat document list can be split into
 * "Personal documents" vs "Work documents" sections.
 */
export async function fetchDocumentTypes(category) {
  const [fixed, custom] = await Promise.all([
    fetchFixedDocumentTypes(category),
    fetchCustomDocumentTypes(category),
  ])

  return [...fixed, ...custom.map((t) => ({ key: t.key, label: t.label, category: t.category }))]
}
