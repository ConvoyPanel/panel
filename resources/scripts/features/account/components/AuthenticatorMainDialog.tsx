import { useShallow } from 'zustand/react/shallow'

import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import { useAuthenticatorModalStore } from '@/features/account/components/AuthenticatorContainer.tsx'
import AuthenticatorStatus from '@/features/account/components/AuthenticatorStatus.tsx'

import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'

const AuthenticatorMainDialog = () => {
    const [open, openModal, closeModal] = useAuthenticatorModalStore(
        useShallow(state => [
            state.activeModal === 'main',
            state.openModal,
            state.closeModal,
        ])
    )

    const handleOpenChange = (open: boolean) => {
        if (open) {
            openModal('main')
        } else {
            closeModal('main')
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={handleOpenChange}>
            <ResponsiveDialogTrigger
                render={
                    <AuthSetting
                        title={'Authenticator'}
                        description={'Time-based verification codes using an app'}
                    />
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Authenticator</ResponsiveDialogTitle>

                    <ResponsiveDialogDescription>
                        Use an authenticator app to generate time-based
                        verification codes.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <ResponsiveDialogBody className={'pb-4 md:pb-0'}>
                    <AuthenticatorStatus />
                </ResponsiveDialogBody>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AuthenticatorMainDialog
