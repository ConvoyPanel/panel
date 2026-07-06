import { IconAsteriskSimple } from '@tabler/icons-react'
import { useShallow } from 'zustand/react/shallow'

import useIsAuthenticatorEnabled from '@/api/account/authenticator/use-is-authenticator-enabled.ts'

import { useAuthenticatorModalStore } from '@/components/interfaces/Client/Security/AuthenticatorContainer.tsx'

import { Button } from '@/components/ui/Button'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'

const AuthenticatorStatus = () => {
    const { data: isEnabled, isLoading } = useIsAuthenticatorEnabled()
    const openModal = useAuthenticatorModalStore(
        useShallow(state => state.openModal)
    )

    if (isLoading) {
        return <Skeleton className={'h-24 w-full'} />
    }

    if (!isEnabled) {
        return (
            <SimpleEmptyState
                icon={IconAsteriskSimple}
                title={'Authenticator is disabled'}
                description={
                    'You have not enabled the authenticator for your account.'
                }
                action={
                    <Button
                        className={'w-full md:w-auto'}
                        onClick={() => openModal('enable')}
                    >
                        Enable
                    </Button>
                }
            />
        )
    }

    return (
        <>
            <p>Authenticator is enabled</p>
            <div className={'mt-3 flex justify-end gap-2'}>
                <Button
                    variant={'outline'}
                    onClick={() => openModal('reset-recovery-codes')}
                >
                    Reset recovery codes
                </Button>
                <Button
                    variant={'destructive'}
                    onClick={() => openModal('disable')}
                >
                    Disable
                </Button>
            </div>
        </>
    )
}

export default AuthenticatorStatus
