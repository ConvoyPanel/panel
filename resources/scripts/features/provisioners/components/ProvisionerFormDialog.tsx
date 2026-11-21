import {
    FIELD_KEY_PATTERN,
    type ProvisionerInput,
    fieldEnvName,
    provisionerQueries,
    provisionerSchema,
    useCreateProvisionerMutation,
    useUpdateProvisionerMutation,
} from '@/features/provisioners/api.ts'
import type { Provisioner } from '@/features/provisioners/types.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus, IconTrash } from '@tabler/icons-react'
import {
    useFieldArray,
    useForm,
    useFormContext,
    useWatch,
} from 'react-hook-form'

import { queryClient } from '@/lib/query-client.ts'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import {
    CheckboxItemForm,
    InputForm,
    TextareaForm,
} from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

/** `'new'` opens an empty form; a provisioner opens it for editing. */
export type ProvisionerDialogTarget =
    | { mode: 'new' }
    | { mode: 'edit'; provisioner: Provisioner }

interface Props {
    target: ProvisionerDialogTarget | null
    close: () => void
}

/** Every variable the runner sets, as ProvisionerEnvironment builds them. */
const SERVER_VARIABLES = [
    'CONVOY_SERVER_UUID',
    'CONVOY_SERVER_NAME',
    'CONVOY_HOSTNAME',
    'CONVOY_IPV4',
    'CONVOY_IPV4_GATEWAY',
    'CONVOY_IPV6',
    'CONVOY_IPV6_GATEWAY',
    'CONVOY_CPU',
    'CONVOY_MEMORY',
    'CONVOY_DISK',
    'CONVOY_IMAGE',
]

const ScriptHint = () => (
    <>
        Runs as root on the first boot. Also set:{' '}
        {SERVER_VARIABLES.map((name, index) => (
            <span key={name}>
                <code className={'font-mono'}>{name}</code>
                {index < SERVER_VARIABLES.length - 1 ? ', ' : '.'}
            </span>
        ))}{' '}
        Memory and disk are in bytes.
    </>
)

const FieldRows = () => {
    const { control } = useFormContext<ProvisionerInput>()
    const { fields, append, remove } = useFieldArray({
        control,
        name: 'fields',
    })
    const values = useWatch({ control, name: 'fields' })

    return (
        <div className={'flex flex-col gap-3'}>
            <div className={'flex items-center justify-between gap-3'}>
                <p className={'text-sm font-medium'}>Fields</p>
                <Button
                    type={'button'}
                    variant={'outline'}
                    size={'sm'}
                    disabled={fields.length >= 20}
                    onClick={() =>
                        append({ key: '', label: '', required: false })
                    }
                >
                    <IconPlus className={'size-4'} /> Add field
                </Button>
            </div>

            {fields.length === 0 ? (
                <p className={'text-muted-foreground text-sm'}>No fields.</p>
            ) : (
                fields.map((field, index) => {
                    const key = values?.[index]?.key ?? ''

                    return (
                        <div
                            key={field.id}
                            className={
                                'grid grid-cols-1 items-start gap-3 rounded-lg border p-3 @md:grid-cols-[1fr_1fr_auto]'
                            }
                        >
                            <InputForm
                                name={`fields.${index}.key`}
                                label={'Key'}
                                className={'font-mono'}
                                autoComplete={'off'}
                                spellCheck={false}
                                description={
                                    FIELD_KEY_PATTERN.test(key) ? (
                                        <code className={'font-mono'}>
                                            {fieldEnvName(key)}
                                        </code>
                                    ) : undefined
                                }
                            />
                            <InputForm
                                name={`fields.${index}.label`}
                                label={'Label'}
                                autoComplete={'off'}
                            />
                            <div
                                className={
                                    'flex h-8 items-center justify-between gap-3 @md:mt-6'
                                }
                            >
                                <CheckboxItemForm
                                    name={`fields.${index}.required`}
                                    label={'Required'}
                                />
                                <Button
                                    type={'button'}
                                    variant={'ghost'}
                                    size={'icon'}
                                    className={'text-destructive'}
                                    onClick={() => remove(index)}
                                    aria-label={'Remove field'}
                                >
                                    <IconTrash className={'size-4'} />
                                </Button>
                            </div>
                        </div>
                    )
                })
            )}
        </div>
    )
}

const ProvisionerFormDialog = ({ target, close }: Props) => {
    const editing = target?.mode === 'edit' ? target.provisioner : null

    const form = useForm<ProvisionerInput>({
        resolver: zodResolver(provisionerSchema),
        values: {
            name: editing?.name ?? '',
            description: editing?.description ?? '',
            script: editing?.script ?? '#!/bin/sh\nset -eu\n\n',
            fields: editing?.fields ?? [],
        },
    })

    const create = useCreateProvisionerMutation()
    const update = useUpdateProvisionerMutation(editing?.uuid ?? '')

    const submit = async (data: ProvisionerInput) => {
        try {
            if (editing) {
                await update.mutateAsync(data)
            } else {
                await create.mutateAsync(data)
            }

            await queryClient.invalidateQueries({
                queryKey: provisionerQueries.all(),
            })

            toast.add({
                title: editing ? 'Provisioner updated' : 'Provisioner created',
                type: 'success',
            })
            close()
        } catch (error) {
            if (!handleFormErrors(error, form.setError)) {
                toast.add({
                    title: 'Failed to save provisioner',
                    type: 'error',
                })
            }
        }
    }

    return (
        <ResponsiveDialog
            open={target !== null}
            onOpenChange={open => !open && close()}
        >
            <ResponsiveDialogContent className={'@container sm:max-w-3xl'}>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {editing ? `Edit ${editing.name}` : 'Add provisioner'}
                    </ResponsiveDialogTitle>
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
                                name={'description'}
                                label={'Description'}
                                autoComplete={'off'}
                            />
                            <TextareaForm
                                name={'script'}
                                label={'Script'}
                                className={'min-h-64 font-mono text-xs'}
                                spellCheck={false}
                                description={<ScriptHint />}
                            />
                            <FieldRows />
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
                                {editing ? 'Save' : 'Add provisioner'}
                            </FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ProvisionerFormDialog
