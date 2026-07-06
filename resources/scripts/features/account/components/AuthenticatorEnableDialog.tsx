import { useEffect } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useShallow } from 'zustand/react/shallow'

import {
    enableAuthenticator,
    authenticatorQueries,
    useQrCode,
    useSecretKey,
} from '@/features/account/authenticator/api.ts'

import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import CredenzaFooter from '@/components/ui/Credenza/CredenzaFooter.tsx'
import Skeleton from '@/components/ui/Skeleton.tsx'

const AuthenticatorEnableDialog = () => {
    const [open, closeModal, pushToQueue] = useAuthenticatorModalStore(
        useShallow(state => [
            state.activeModal === 'enable',
            state.closeModal,
            state.pushToQueue,
        ])
    )
    const queryClient = useQueryClient()
    const { data: qrCode, isLoading, error } = useQrCode()
    const { data: secretKey } = useSecretKey()

    useEffect(() => {
        const main = async () => {
            if (open) {
                await enableAuthenticator()
                await queryClient.invalidateQueries({
                    queryKey: authenticatorQueries.qrCode().queryKey,
                })
                await queryClient.invalidateQueries({
                    queryKey: authenticatorQueries.secretKey().queryKey,
                })
            }
        }

        main()
    }, [open])

    const handleOpenChange = (open: boolean) => {
        if (!open) {
            pushToQueue('recovery-codes')
            closeModal('enable')
        }
    }

    return (
        <Credenza open={open} onOpenChange={handleOpenChange}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Enable Authenticator</CredenzaTitle>
                    <CredenzaDescription>
                        To finish enabling two factor authentication, scan the
                        following QR code using your phone's authenticator
                        application or enter the setup key and provide the
                        generated OTP code.
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaBody>
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
                </CredenzaBody>

                <CredenzaFooter>
                    <CredenzaClose asChild>
                        <Button>Next</Button>
                    </CredenzaClose>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default AuthenticatorEnableDialog
