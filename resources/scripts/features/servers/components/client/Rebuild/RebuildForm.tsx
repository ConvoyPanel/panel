import { useIdentityConfirmed } from '@/features/auth/identity/api.ts'
import RebuildConfirmDialog from '@/features/servers/components/client/Rebuild/RebuildConfirmDialog'
import TemplateField from '@/features/servers/components/client/Rebuild/TemplateField'
import TemplateGroupField from '@/features/servers/components/client/Rebuild/TemplateGroupField'
import {
    type ReinstallServerInput,
    reinstallServer,
    reinstallServerSchema,
    serverQueries,
    useTemplateGroups,
} from '@/features/servers/detail/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Server, ServerLifecycle } from '@/types/server'
import { handleFormErrors } from '@/utils/http'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useNavigate } from '@tanstack/react-router'
import { useEffect, useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'
import { FieldGroup } from '@/components/ui/Field'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm } from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

interface Props {
    server: Server
}

const defaultValues: ReinstallServerInput = {
    templateGroupUuid: '',
    templateUuid: '',
    accountPassword: '',
    accountPasswordConfirmation: '',
    startOnCompletion: true,
}

const RebuildForm = ({ server }: Props) => {
    const navigate = useNavigate()
    const mutateServer = useQueryMutator<Server>(
        serverQueries.detail(server.uuid).queryKey
    )
    const { data: templateGroups } = useTemplateGroups(server.uuid)
    const [isConfirming, setConfirming] = useState(false)

    const form = useForm<ReinstallServerInput>({
        resolver: zodResolver(reinstallServerSchema),
        defaultValues,
    })

    const templateGroupUuid = form.watch('templateGroupUuid')
    const templateUuid = form.watch('templateUuid')

    const selectedGroup = useMemo(
        () => templateGroups?.find(group => group.uuid === templateGroupUuid),
        [templateGroups, templateGroupUuid]
    )
    const selectedTemplate = selectedGroup?.templates?.find(
        template => template.uuid === templateUuid
    )

    useEffect(() => {
        form.setValue('templateUuid', '')
    }, [templateGroupUuid, form])

    const { mutateAsync: trigger, isPending } = useMutation({
        mutationFn: (data: ReinstallServerInput) =>
            reinstallServer(server.uuid, {
                templateUuid: data.templateUuid,
                accountPassword: data.accountPassword,
                startOnCompletion: data.startOnCompletion,
            }),
        onSuccess: () => {
            setConfirming(false)
            toast.add({
                title: 'The installation started successfully.',
                type: 'success',
            })
            // The server layout swaps to <InstallingServer /> off `lifecycle`,
            // so patch the cached record rather than only invalidating: the
            // refetch is a whole round trip during which this form is still on
            // screen, which reads as the rebuild not having started at all.
            // RebuildServerAction has already written INSTALLING by the time
            // this response comes back, so the revalidation that follows
            // confirms it rather than correcting it.
            mutateServer(current =>
                current
                    ? { ...current, lifecycle: ServerLifecycle.Installing }
                    : current
            )
            // Leave the rebuild URL behind. The layout covers the Outlet with
            // the install screen either way, but the route underneath is still
            // this form — so the moment the install finishes it reappears, and
            // landing back on "Erase disk and install" reads as though nothing
            // happened. `replace` so Back does not return to it either.
            navigate({
                to: '/servers/$serverUuid',
                params: { serverUuid: server.uuid },
                replace: true,
            })
        },
        onError: e => {
            setConfirming(false)

            if (handleFormErrors(e, form.setError)) return

            toast.add({ title: 'Failed to start installation.', type: 'error' })
        },
    })

    // Rebuilding erases the disk, so ReinstallServerRequest demands a confirmed
    // identity for it. Ask here rather than letting the submit 403 with only a
    // toast to show for it — same shape as PasswordCard's gate.
    const identityConfirmed = useIdentityConfirmed()
    const [pending, setPending] = useState<ReinstallServerInput | null>(null)

    useEffect(() => {
        if (!pending || !identityConfirmed) return

        const data = pending
        setPending(null)
        // onError already surfaces the failure; nothing awaits this one.
        void trigger(data).catch(() => {})
    }, [pending, identityConfirmed])

    const rebuild = () => {
        const data = form.getValues()

        if (!identityConfirmed) {
            setPending(data)

            return
        }

        void trigger(data).catch(() => {})
    }

    // Submitting opens the confirmation rather than rebuilding, so the fields
    // are validated before anyone is asked to type a server name.
    const confirm = () => setConfirming(true)

    return (
        <Form {...form}>
            <form
                onSubmit={form.handleSubmit(confirm)}
                className={'grid grid-cols-1 gap-2 @md:gap-4'}
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Operating system</CardTitle>
                        <CardDescription>
                            Choose the image to install.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <TemplateGroupField groups={templateGroups} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Version</CardTitle>
                        <CardDescription>
                            {selectedGroup
                                ? `Which release of ${selectedGroup.name} to install.`
                                : 'Pick a release once you have chosen an operating system.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <TemplateField group={selectedGroup} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Password</CardTitle>
                        <CardDescription>
                            Set the password for the default account on the
                            fresh install.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <FieldGroup>
                            <InputForm
                                name={'accountPassword'}
                                label={'Password'}
                                type={'password'}
                                autoComplete={'new-password'}
                            />
                            <InputForm
                                name={'accountPasswordConfirmation'}
                                label={'Confirm password'}
                                type={'password'}
                                autoComplete={'new-password'}
                            />
                            <CheckboxForm
                                name={'startOnCompletion'}
                                label={
                                    'Start the server when the install finishes'
                                }
                                description={
                                    'Leave it off to attach an ISO or change settings before the first boot.'
                                }
                            />
                        </FieldGroup>
                    </CardContent>
                    <CardFooter className={'justify-end gap-2'}>
                        <FormButton variant={'destructive'}>
                            Erase disk and install
                        </FormButton>
                    </CardFooter>
                </Card>
            </form>

            <RebuildConfirmDialog
                open={isConfirming}
                onOpenChange={setConfirming}
                serverName={server.name}
                disk={server.disk}
                templateLabel={`${selectedGroup?.name ?? ''} ${
                    selectedTemplate?.name ?? ''
                }`.trim()}
                // The gate holds the submit open, so the button has to keep
                // reading as in flight while it is up.
                isPending={isPending || pending !== null}
                onConfirm={rebuild}
            >
                {/* Mounted only while a submit is waiting on it — AuthDialog
                    opens itself whenever identity is unconfirmed, so leaving it
                    mounted would raise the gate on anyone who merely opened the
                    confirmation. */}
                {pending !== null && (
                    <AuthDialog onCancel={() => setPending(null)} />
                )}
            </RebuildConfirmDialog>
        </Form>
    )
}

export default RebuildForm
