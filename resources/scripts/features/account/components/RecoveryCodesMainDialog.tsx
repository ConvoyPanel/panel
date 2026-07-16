import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import RecoveryCodeList from '@/features/account/components/RecoveryCodeList.tsx'
import { useRecoveryCodesModalStore } from '@/features/account/components/RecoveryCodesContainer.tsx'
import RecoveryCodesResetDialog from '@/features/account/components/RecoveryCodesResetDialog.tsx'
import {
    recoveryCodeQueries,
    useHasRecoveryCodes,
} from '@/features/account/recovery-codes/api.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import useClipboard from '@/hooks/use-clipboard.ts'
import useIdentityConfirmed from '@/hooks/use-identity-confirmed.ts'
import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'
import { CollectionErrorState } from '@/components/ui/EmptyStates'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'

/**
 * The single management surface for the account's recovery codes.
 *
 * Viewing and resetting used to live inside *both* the Authenticator and the
 * Passkeys dialog, which read as two independent sets of codes. There is only
 * one: `users.two_factor_recovery_codes`, minted by whichever second factor is
 * enabled first. "Reset recovery codes" in the Authenticator dialog silently
 * invalidated the codes the Passkeys dialog had just told the user to save.
 *
 * So the codes get their own row beside Authenticator and Passkeys, and those
 * dialogs keep only the one-time reveal of what their own flow just minted.
 */
const RecoveryCodesMainDialog = () => {
    const [open, setOpen] = useState(false)
    const { data: hasCodes } = useHasRecoveryCodes()
    const openModal = useOpenModal(useRecoveryCodesModalStore)
    const { copy } = useClipboard({
        successMessage: 'Copied recovery codes to clipboard',
    })

    // Two gates, both required: the endpoint is behind
    // RequireIdentityConfirmation (see useIdentityConfirmed), and codes are a
    // secret worth not holding in memory until the user actually asks to see
    // them.
    const isIdentityConfirmed = useIdentityConfirmed()
    const {
        data: codes,
        isPending,
        isError,
        refetch,
    } = useQuery({
        ...recoveryCodeQueries.codes(),
        enabled: open && isIdentityConfirmed,
        // They are read once and copied down; a stale set is a wrong set.
        staleTime: 0,
        gcTime: 0,
    })

    // Nothing to recover to yet. The first second factor mints the codes, and
    // removing the last one clears them, so this row tracks that exactly.
    if (!hasCodes) return null

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <AuthSetting
                        title={'Recovery codes'}
                        description={
                            'One-time codes to sign in if you lose your other methods'
                        }
                    />
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Recovery codes
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Each code signs you in once if you lose access to your
                        authenticator or passkeys. Store them somewhere safe.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <ResponsiveDialogBody>
                    {isPending ? (
                        <Skeleton className={'h-40 w-full'} />
                    ) : isError ? (
                        <CollectionErrorState onRetry={refetch} />
                    ) : (
                        <RecoveryCodeList codes={codes} />
                    )}
                </ResponsiveDialogBody>

                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Close</Button>}
                    />
                    <Button
                        variant={'outline'}
                        disabled={!codes}
                        onClick={() => codes && copy(codes.join('\n'))}
                    >
                        Copy codes
                    </Button>
                    <Button
                        variant={'outline'}
                        onClick={() => openModal('reset')}
                    >
                        Reset codes
                    </Button>
                </ResponsiveDialogFooter>

                {/* Nested inside the parent's content: Base UI gives these no
                    backdrop of their own, so the parent stays visible (scaled
                    back) underneath rather than being torn down between steps. */}
                <AuthDialog onCancel={() => setOpen(false)} />
                <RecoveryCodesResetDialog />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default RecoveryCodesMainDialog
