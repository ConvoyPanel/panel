import createModalStore from '@/hooks/create-modal-store.ts'
import { Passkey } from '@/features/account/types.ts'

import PasskeysMainDialog from '@/features/account/components/PasskeysMainDialog.tsx'

/**
 * Tracks which passkey sub-dialog is open, and the row it acts on. The
 * sub-dialogs are nested inside the main dialog, and the identity gate is a
 * nested <AuthDialog> — no queue, no middleware.
 */
export const usePasskeysModalStore = createModalStore<
    Passkey,
    'rename' | 'delete'
>()

const PasskeysContainer = () => <PasskeysMainDialog />

export default PasskeysContainer
