import createModalStore from '@/hooks/create-modal-store.ts'

import AuthenticatorDisableDialog from '@/features/account/components/AuthenticatorDisableDialog.tsx'
import AuthenticatorEnableDialog from '@/features/account/components/AuthenticatorEnableDialog.tsx'
import AuthenticatorMainDialog from '@/features/account/components/AuthenticatorMainDialog.tsx'
import AuthenticatorRecoveryCodesDialog from '@/features/account/components/AuthenticatorRecoveryCodesDialog.tsx'
import AuthenticatorResetRecoveryCodesDialog from '@/features/account/components/AuthenticatorResetRecoveryCodesDialog.tsx'

import AuthDialog, {
    createAuthMiddleware,
} from '@/components/ui/Dialog/AuthDialog.tsx'

export const useAuthenticatorModalStore = createModalStore<
    any,
    | 'main'
    | 'enable'
    | 'disable'
    | 'reset-recovery-codes'
    | 'recovery-codes'
    | 'auth'
>({
    main: {
        middleware: createAuthMiddleware('auth'),
    },
})

const AuthenticatorContainer = () => {
    return (
        <>
            <AuthenticatorMainDialog />
            <AuthenticatorEnableDialog />
            <AuthenticatorDisableDialog />
            <AuthenticatorRecoveryCodesDialog />
            <AuthenticatorResetRecoveryCodesDialog />
            <AuthDialog useModalStore={useAuthenticatorModalStore} />
        </>
    )
}

export default AuthenticatorContainer
