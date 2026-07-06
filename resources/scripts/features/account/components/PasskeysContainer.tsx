import createModalStore from '@/hooks/create-modal-store.ts'
import { Passkey } from '@/features/account/types.ts'

import PasskeyDeleteDialog from '@/features/account/components/PasskeyDeleteDialog.tsx'
import PasskeyRenameDialog from '@/features/account/components/PasskeyRenameDialog.tsx'
import PasskeysMainDialog from '@/features/account/components/PasskeysMainDialog.tsx'

import AuthDialog, {
    createAuthMiddleware,
} from '@/components/ui/Dialog/AuthDialog.tsx'


export const usePasskeysModalStore = createModalStore<
    Passkey,
    'main' | 'rename' | 'delete' | 'auth'
>({
    main: {
        middleware: createAuthMiddleware('auth'),
    },
})

const PasskeysContainer = () => {
    return (
        <>
            <PasskeysMainDialog />
            <PasskeyRenameDialog />
            <PasskeyDeleteDialog />
            <AuthDialog useModalStore={usePasskeysModalStore} />
        </>
    )
}

export default PasskeysContainer
