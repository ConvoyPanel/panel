import { shareServer, subuserQueries } from '@/features/subusers/api.ts'
import PermissionPicker from '@/features/subusers/components/PermissionPicker.tsx'
import {
    type ShareServerInput,
    shareServerSchema,
} from '@/features/subusers/types.ts'
import type { UserInvite } from '@/features/users/api.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconMailForward, IconPlus } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'

import { queryClient } from '@/lib/query-client.ts'

import { Button } from '@/components/ui/Button'
import CopyValue from '@/components/ui/CopyValue.tsx'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

interface Props {
    server: string
}

const defaultValues: ShareServerInput = { email: '', permissions: [] }

const ShareServerDialog = ({ server }: Props) => {
    const [open, setOpen] = useState(false)
    const [invite, setInvite] = useState<UserInvite | null>(null)

    const form = useForm<ShareServerInput>({
        resolver: zodResolver(shareServerSchema),
        defaultValues,
    })

    const { mutateAsync: submit } = useMutation({
        mutationFn: (data: ShareServerInput) => shareServer(server, data),
    })

    const close = () => {
        setOpen(false)
        setInvite(null)
        // Reset after the close animation so the form doesn't flash.
        setTimeout(() => form.reset(defaultValues), 200)
    }

    const onSubmit = async (data: ShareServerInput) => {
        try {
            const result = await submit(data)

            await queryClient.invalidateQueries({
                queryKey: subuserQueries.all(server),
            })

            // An invited account leaves the dialog open on the link. Closing straight into a
            // toast would throw away the one thing there is to pass on.
            if (result.invite) {
                setInvite(result.invite)

                return
            }

            toast.add({ title: 'Server shared', type: 'success' })
            close()
        } catch (error) {
            handleFormErrors(error, form.setError)
            toast.add({ title: 'Failed to share server', type: 'error' })
        }
    }

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={next => (next ? setOpen(true) : close())}
        >
            <ResponsiveDialogTrigger
                render={
                    <Button>
                        <IconPlus className={'size-4'} /> Share server
                    </Button>
                }
            />
            <ResponsiveDialogContent className={'@container sm:max-w-2xl'}>
                {invite ? (
                    <>
                        <ResponsiveDialogHeader>
                            <ResponsiveDialogTitle>
                                Invitation sent
                            </ResponsiveDialogTitle>
                        </ResponsiveDialogHeader>
                        <ResponsiveDialogBody className={'flex flex-col gap-3'}>
                            <p className={'text-muted-foreground text-sm'}>
                                {invite.emailed
                                    ? 'The link is below as well.'
                                    : 'Mail is not set up. Send this link yourself.'}
                            </p>
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
                                {new Date(
                                    invite.expiresAt
                                ).toLocaleDateString()}
                                .
                            </p>
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <Button onClick={close}>Done</Button>
                        </ResponsiveDialogFooter>
                    </>
                ) : (
                    <>
                        <ResponsiveDialogHeader>
                            <ResponsiveDialogTitle>
                                Share server
                            </ResponsiveDialogTitle>
                        </ResponsiveDialogHeader>
                        <Form {...form}>
                            <form onSubmit={form.handleSubmit(onSubmit)}>
                                <ResponsiveDialogBody
                                    className={'flex flex-col gap-5'}
                                >
                                    <InputForm
                                        name={'email'}
                                        label={'Email'}
                                        type={'email'}
                                        autoComplete={'off'}
                                    />
                                    <PermissionPicker name={'permissions'} />
                                </ResponsiveDialogBody>
                                <ResponsiveDialogFooter className={'mt-4'}>
                                    <ResponsiveDialogClose
                                        render={
                                            <Button
                                                variant={'outline'}
                                                type={'button'}
                                            >
                                                Cancel
                                            </Button>
                                        }
                                    />
                                    <FormButton>Share</FormButton>
                                </ResponsiveDialogFooter>
                            </form>
                        </Form>
                    </>
                )}
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ShareServerDialog
