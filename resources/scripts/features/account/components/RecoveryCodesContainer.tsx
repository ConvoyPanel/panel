import RecoveryCodesMainDialog from '@/features/account/components/RecoveryCodesMainDialog.tsx'
import createModalStore from '@/hooks/create-modal-store.ts'

/**
 * Tracks which step of the recovery-code flow is open. The steps are nested
 * inside the main dialog, so opening one leaves the parent mounted underneath —
 * the identity gate is a nested <AuthDialog>, same as the other families.
 */
export const useRecoveryCodesModalStore = createModalStore<void, 'reset'>()

const RecoveryCodesContainer = () => <RecoveryCodesMainDialog />

export default RecoveryCodesContainer
