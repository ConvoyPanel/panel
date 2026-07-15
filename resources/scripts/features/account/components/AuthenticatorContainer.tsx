import createModalStore from '@/hooks/create-modal-store.ts'

import AuthenticatorMainDialog from '@/features/account/components/AuthenticatorMainDialog.tsx'

/**
 * Tracks which step of the authenticator flow is open. The steps are nested
 * inside the main dialog, so opening one leaves the parent mounted underneath —
 * there is no queue and no auth middleware here any more: the gate is a nested
 * <AuthDialog> that opens itself while identity is unconfirmed.
 */
export const useAuthenticatorModalStore = createModalStore<
    any,
    'enable' | 'disable' | 'reset-recovery-codes' | 'recovery-codes'
>()

const AuthenticatorContainer = () => <AuthenticatorMainDialog />

export default AuthenticatorContainer
