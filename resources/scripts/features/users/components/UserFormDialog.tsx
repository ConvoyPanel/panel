import { createUser, updateUser } from '@/features/users/api.ts'
import {
    type UserInput,
    createUserSchema,
    updateUserSchema,
} from '@/features/users/types.ts'
import type { AdminUser } from '@/types/admin/user.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconAlertTriangle } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
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

    const save = useMutation({
        mutationFn: (data: UserInput) =>
            current ? updateUser(current.id, data) : createUser(data),
    })

    const submit = async (data: UserInput) => {
        try {
            await save.mutateAsync(data)
            await refresh()
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

    const password = form.watch('password')

    return (
        <ResponsiveDialog
            open={user !== null}
            onOpenChange={open => !open && close()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {current ? `Edit ${current.name}` : 'Add user'}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        {current
                            ? 'Changes apply the next time they load the panel.'
                            : 'They sign in with the email and password you set here.'}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'flex flex-col gap-5'}>
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
                                        : undefined
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
                                // Base UI renders the checkbox as a span, so
                                // `disabled:` never matches and the control
                                // looks live — the same reason AnchorFormDialog
                                // dims its locked role field at the call site.
                                formItemProps={
                                    cannotSelfDemote
                                        ? { className: 'opacity-60' }
                                        : undefined
                                }
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
