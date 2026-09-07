import MailNotConfiguredAlert from '@/features/settings/components/MailNotConfiguredAlert.tsx'
import {
    type UserInvite,
    createUser,
    updateUser,
} from '@/features/users/api.ts'
import {
    type UserInput,
    createUserSchema,
    updateUserSchema,
} from '@/features/users/types.ts'
import type { AdminUser } from '@/types/admin/user.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconAlertTriangle, IconMailForward } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import CopyValue from '@/components/ui/CopyValue.tsx'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm } from '@/components/ui/Forms'
import PasswordStrengthIndicator from '@/components/ui/Password/PasswordStrengthIndicator.tsx'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

interface Props {
    /** The account being edited, `'new'` for the create flow, `null` when closed. */
    user: AdminUser | 'new' | null
    /** The signed-in admin, so the form can refuse to lock them out of their own panel. */
    currentUserId: number | undefined
    close: () => void
    refresh: () => Promise<unknown>
}

/**
 * One dialog for both create and edit, the way AnchorFormDialog is: the questions are the same
 * either way, and only the password differs — a new account has to be given one, an existing
 * account already has one, so a blank box leaves it untouched.
 */
const UserFormDialog = ({ user, currentUserId, close, refresh }: Props) => {
    const current = user === 'new' ? null : user
    // Mirrors the server's guard in Admin\UserController::update. Enforced there because it has
    // to be; shown here because a control that will be rejected should not look available.
    const cannotSelfDemote =
        current !== null && current.rootAdmin && current.id === currentUserId

    const form = useForm<UserInput>({
        resolver: zodResolver(current ? updateUserSchema : createUserSchema),
        values: {
            name: current?.name ?? '',
            email: current?.email ?? '',
            rootAdmin: current?.rootAdmin ?? false,
            password: '',
        },
    })

    // Held rather than toasted: the link is the only copy that will ever exist, and a toast
    // that slides away four seconds after an admin's eyes moved is not somewhere to put it.
    const [invite, setInvite] = useState<UserInvite | null>(null)

    const save = useMutation({
        mutationFn: async (data: UserInput) => {
            if (current)
                return {
                    user: await updateUser(current.id, data),
                    invite: null,
                }

            return createUser(data)
        },
    })

    const submit = async (data: UserInput) => {
        try {
            const result = await save.mutateAsync(data)
            await refresh()

            // An invited account leaves the dialog open on the link. Closing straight into a
            // toast would throw away the one thing the admin now has to pass on.
            if (result.invite) {
                setInvite(result.invite)

                return
            }

            toast.add({
                title: `User ${current ? 'updated' : 'created'}`,
                type: 'success',
            })
            close()
        } catch (error) {
            handleFormErrors(error, form.setError)
            toast.add({ title: 'Failed to save user', type: 'error' })
        }
    }

    const dismiss = () => {
        setInvite(null)
        close()
    }

    const password = form.watch('password')

    /*
     * The account exists and the link has been minted. It is shown rather than toasted because
     * this is the only time it is readable — the row stores a hash — and because on an install
     * with no working relay this link is the entire handover.
     */
    if (invite) {
        return (
            <ResponsiveDialog open onOpenChange={open => !open && dismiss()}>
                <ResponsiveDialogContent>
                    <ResponsiveDialogHeader>
                        <ResponsiveDialogTitle>
                            Account created
                        </ResponsiveDialogTitle>
                        <ResponsiveDialogDescription>
                            {invite.emailed
                                ? 'Invitation emailed. The link is below too.'
                                : "Mail isn't set up. Send this link yourself."}
                        </ResponsiveDialogDescription>
                    </ResponsiveDialogHeader>
                    <ResponsiveDialogBody className={'flex flex-col gap-3'}>
                        <div
                            className={
                                'bg-muted/50 flex items-start gap-2 rounded-lg border p-3'
                            }
                        >
                            <IconMailForward
                                className={
                                    'text-muted-foreground mt-0.5 size-4 shrink-0'
                                }
                            />
                            <CopyValue
                                label={'invitation link'}
                                value={invite.link}
                                className={'text-xs break-all'}
                            />
                        </div>
                        <p className={'text-muted-foreground text-sm'}>
                            Single use. Expires{' '}
                            {new Date(invite.expiresAt).toLocaleDateString()}.
                        </p>
                    </ResponsiveDialogBody>
                    <ResponsiveDialogFooter className={'mt-4'}>
                        <Button onClick={dismiss}>Done</Button>
                    </ResponsiveDialogFooter>
                </ResponsiveDialogContent>
            </ResponsiveDialog>
        )
    }

    return (
        <ResponsiveDialog
            open={user !== null}
            onOpenChange={open => !open && dismiss()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {current ? `Edit ${current.name}` : 'Add user'}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        {current
                            ? 'Changes apply the next time they load the panel.'
                            : 'Set a password, or leave it blank to send an invite.'}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'flex flex-col gap-5'}>
                            {/* Only on create: an existing account's owner has
                                already been told how to sign in, and the warning
                                belongs where the cost is incurred rather than on
                                every visit to this dialog. */}
                            {current === null && (
                                <MailNotConfiguredAlert>
                                    Nothing will be emailed to this account.
                                </MailNotConfiguredAlert>
                            )}
                            <InputForm
                                name={'name'}
                                label={'Name'}
                                autoComplete={'off'}
                            />
                            <InputForm
                                name={'email'}
                                label={'Email'}
                                type={'email'}
                                autoComplete={'off'}
                            />
                            <InputForm
                                name={'password'}
                                label={'Password'}
                                type={'password'}
                                autoComplete={'new-password'}
                                description={
                                    current
                                        ? 'Leave blank to keep their current password.'
                                        : 'Blank sends an invite instead.'
                                }
                            />
                            {/* Only once there is something to grade: on an edit
                                the field is usually left alone, and a column of
                                red crosses under an empty box reads as a
                                failure rather than as guidance. */}
                            {password !== '' && (
                                <PasswordStrengthIndicator
                                    password={password}
                                />
                            )}
                            <CheckboxForm
                                name={'rootAdmin'}
                                label={'Administrator'}
                                description={
                                    'Full access to every node, server and account in the panel.'
                                }
                                disabled={cannotSelfDemote}
                            />
                            {cannotSelfDemote && (
                                <Alert>
                                    <IconAlertTriangle className={'size-4'} />
                                    <AlertDescription>
                                        You can't remove your own administrator
                                        access. Another administrator can do it
                                        for you.
                                    </AlertDescription>
                                </Alert>
                            )}
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>
                                {current ? 'Save' : 'Add user'}
                            </FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default UserFormDialog
