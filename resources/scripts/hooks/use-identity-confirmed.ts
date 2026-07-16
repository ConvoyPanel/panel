import useIdentityConfirmationStore from '@/stores/identity-confirmation-store.ts'

/**
 * Whether identity is currently confirmed, i.e. whether the identity-gated
 * account endpoints will answer rather than 403.
 *
 * Reach for this as the `enabled` of any query behind RequireIdentityConfirmation.
 * Firing one before the gate is satisfied does not merely waste a request: React
 * Query retries the 403 three times with exponential backoff, so the query stays
 * pending for ~7s while the user confirms, and whichever retry happens to land
 * after confirmation is what finally paints — a multi-second skeleton with no
 * relation to how long anything took. Confirming flips this true and re-renders
 * the subscriber, which is the fetch trigger.
 */
const useIdentityConfirmed = (): boolean =>
    useIdentityConfirmationStore(state => state.isIdentityValid())

export default useIdentityConfirmed
