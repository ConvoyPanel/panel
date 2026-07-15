import {
    authenticatorQueries,
    enableAuthenticator,
    useQrCode,
    useSecretKey,
} from '@/features/account/authenticator/api.ts'
import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'
import { useQueryClient } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { useShallow } from 'zustand/react/shallow'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { ResponsiveDialogFooter } from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'

const AuthenticatorEnableDialog = () => {
    const [open, openModal] = useAuthenticatorModalStore(
        useShallow(state => [state.activeModal === 'enable', state.openModal])
    )
    const queryClient = useQueryClient()
    const [setupReady, setSetupReady] = useState(false)
    const { data: qrCode, isLoading, error } = useQrCode(open && setupReady)
    const { data: secretKey } = useSecretKey(open && setupReady)

    useEffect(() => {
        let cancelled = false

        const main = async () => {
            if (!open) {
                setSetupReady(false)

                return
            }

            setSetupReady(false)
            await enableAuthenticator()

            if (cancelled) {
                return
            }

            await queryClient.invalidateQueries({
                queryKey: authenticatorQueries.qrCode().queryKey,
            })
            await queryClient.invalidateQueries({
                queryKey: authenticatorQueries.secretKey().queryKey,
            })
            setSetupReady(true)
        }

        void main()

        return () => {
            cancelled = true
        }
    }, [open, queryClient])

    const handleOpenChange = (open: boolean) => {
        if (!open) {
            // openModal replaces the active step, so this both closes 'enable'
            // and reveals the codes — no queue involved.
            openModal('recovery-codes')
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={handleOpenChange}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Enable Authenticator</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        To finish enabling two factor authentication, scan the
                        following QR code using your phone's authenticator
                        application or enter the setup key and provide the
                        generated OTP code.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    {error ||
                    isLoading ||
                    !Boolean(qrCode?.url) ||
                    !secretKey ? (
                        <Skeleton className={'h-24 w-full'} />
                    ) : (
                        <>
                            <div
                                className={'grid place-items-center'}
                                dangerouslySetInnerHTML={{
                                    __html: qrCode!.svg,
                                }}
                            />
                            <p className={'mt-3 text-center'}>
                                <strong>Secret Key:</strong> {secretKey}
                            </p>
                        </>
                    )}
                </ResponsiveDialogBody>

                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={
                            <Button>Next</Button>
                        }
                    />
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AuthenticatorEnableDialog
