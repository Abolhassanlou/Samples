/**
 * Stored in work_authorization_type when a worker says they hold an
 * Austrian/EU/EEA/Swiss passport and so need no visa. Shared so the
 * profile form (which writes it) and the Home notice (which words its
 * message by it) can't drift apart.
 */
export const EU_CITIZEN_MARKER = 'EU/EEA/Swiss/Austrian citizen — no visa required'
