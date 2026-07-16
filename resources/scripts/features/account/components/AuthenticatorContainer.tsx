import AuthenticatorMainDialog from '@/features/account/components/AuthenticatorMainDialog.tsx'
import createModalStore from '@/hooks/create-modal-store.ts'

/**
 * Tracks which step of the authenticator flow is open. The steps are nested
 * inside the main dialog, so opening one leaves the parent mounted underneath —
 * there is no queue and no auth middleware here any more: the gate is a nested
 * <AuthDialog> that opens itself while identity is unconfirmed.
 */
export const useAuthenticatorModalStore = createModalStore<
    void,
    'enable' | 'disable'
>()

const AuthenticatorContainer = () => <AuthenticatorMainDialog />

export default AuthenticatorContainer
