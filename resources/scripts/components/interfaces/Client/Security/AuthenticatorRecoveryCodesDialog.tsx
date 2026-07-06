import { useEffect } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import useRecoveryCodes, {
    getKey as getRecoveryCodesKey,
} from '@/api/account/authenticator/use-recovery-codes.ts'

import { useAuthenticatorModalStore } from '@/components/interfaces/Client/Security/AuthenticatorContainer.tsx'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import Skeleton from '@/components/ui/Skeleton.tsx'

const AuthenticatorRecoveryCodesDialog = () => {
    const [open, closeModal] = useAuthenticatorModalStore(
        useShallow(state => [
            state.activeModal === 'recovery-codes',
            state.closeModal,
        ])
    )

    const queryClient = useQueryClient()
    const { data: codes } = useRecoveryCodes()

    useEffect(() => {
        if (open) {
            queryClient.invalidateQueries({ queryKey: getRecoveryCodesKey() })
        }
    }, [open])

    const copy = async () => {
        if (!codes) {
            return
        }

        await navigator.clipboard.writeText(codes.join('\n'))

        toast.success('Copied recovery codes to clipboard')
    }

    return (
        <Credenza
            open={open}
            onOpenChange={open => !open && closeModal('recovery-codes')}
        >
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Recovery Codes</CredenzaTitle>
                    <CredenzaDescription>
                        Store these recovery codes in a safe place. If you lose
                        access to your authenticator app, you can use these
                        codes to recover your account.
                    </CredenzaDescription>
                </CredenzaHeader>

                <CredenzaBody>
                    {codes ? (
                        <ul className={'text-center'}>
                            {codes.map(code => (
                                <li key={code}>{code}</li>
                            ))}
                        </ul>
                    ) : (
                        <Skeleton className={'h-48 w-full'} />
                    )}
                </CredenzaBody>

                <CredenzaFooter>
                    <Button variant={'outline'} onClick={copy}>
                        Copy codes
                    </Button>
                    <CredenzaClose asChild>
                        <Button variant={'destructive'}>
                            I saved my codes
                        </Button>
                    </CredenzaClose>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default AuthenticatorRecoveryCodesDialog
