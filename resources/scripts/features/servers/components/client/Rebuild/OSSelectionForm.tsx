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
import { useEffect, useMemo } from 'react'
import { useForm } from 'react-hook-form'

import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldTitle,
} from '@/components/ui/Field'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm } from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

interface OSSelectionFormProps {
    server: Server
}

const defaultValues: ReinstallServerInput = {
    templateGroupUuid: '',
    templateUuid: '',
    accountPassword: '',
    accountPasswordConfirmation: '',
    startOnCompletion: true,
}

/**
 * First-install picker, for a server sitting in DEFERRED_OS_SELECTION. Same
 * fields as the rebuild page and the same request, but no disk to erase yet —
 * hence no destructive framing and no type-the-name confirmation. Rebuilding a
 * live server goes through <RebuildForm /> instead.
 */
const OSSelectionForm = ({ server }: OSSelectionFormProps) => {
    const navigate = useNavigate()
    const mutateServer = useQueryMutator<Server>(
        serverQueries.detail(server.uuid).queryKey
    )
    const { data: templateGroups } = useTemplateGroups(server.uuid)

    const form = useForm<ReinstallServerInput>({
        resolver: zodResolver(reinstallServerSchema),
        defaultValues,
    })

    const templateGroupUuid = form.watch('templateGroupUuid')

    const selectedGroup = useMemo(
        () => templateGroups?.find(group => group.uuid === templateGroupUuid),
        [templateGroups, templateGroupUuid]
    )

    useEffect(() => {
        form.setValue('templateUuid', '')
    }, [templateGroupUuid, form])

    const { mutateAsync: trigger } = useMutation({
        mutationFn: (data: ReinstallServerInput) =>
            reinstallServer(server.uuid, {
                templateUuid: data.templateUuid,
                accountPassword: data.accountPassword,
                startOnCompletion: data.startOnCompletion,
            }),
        onSuccess: () => {
            toast.add({
                title: 'The installation started successfully.',
                type: 'success',
            })
            // Patch the record rather than only invalidating it: the layout
            // swaps to <InstallingServer /> off `lifecycle`, and waiting for
            // the refetch to land leaves this form on screen for a whole round
            // trip. See RebuildForm, which does the same.
            mutateServer(current =>
                current
                    ? { ...current, lifecycle: ServerLifecycle.Installing }
                    : current
            )
            // This screen covers the Outlet wherever the user happens to be,
            // including the rebuild page — so send them to the dashboard, or
            // the install finishing puts them back on a form for the install
            // that just ran. See RebuildForm.
            navigate({
                to: '/servers/$serverUuid',
                params: { serverUuid: server.uuid },
                replace: true,
            })
        },
        onError: e => {
            if (handleFormErrors(e, form.setError)) return

            toast.add({ title: 'Failed to start installation.', type: 'error' })
        },
    })

    const submit = async (data: ReinstallServerInput) => {
        await trigger(data).catch(() => {})
    }

    return (
        <Form {...form}>
            <form
                onSubmit={form.handleSubmit(submit)}
                className={'flex flex-col gap-4'}
            >
                <FieldGroup>
                    <Field>
                        <FieldTitle>Operating system</FieldTitle>
                        <TemplateGroupField groups={templateGroups} />
                    </Field>

                    <Field>
                        <FieldTitle>Version</FieldTitle>
                        <TemplateField group={selectedGroup} />
                    </Field>

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
                        label={'Start the server when the install finishes'}
                        description={
                            'Leave it off to attach an ISO or change settings before the first boot.'
                        }
                    />
                </FieldGroup>

                <FieldDescription>
                    Installation progress replaces this screen once it starts.
                </FieldDescription>

                <FormButton className={'w-full'}>
                    Install operating system
                </FormButton>
            </form>
        </Form>
    )
}

export default OSSelectionForm
