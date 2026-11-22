import {
    type WebhookInput,
    createWebhook,
    updateWebhook,
    webhookQueries,
    webhookSchema,
} from '@/features/webhooks/api.ts'
import EventPicker from '@/features/webhooks/components/EventPicker.tsx'
import {
    SECRET_DESCRIPTION,
    SecretValue,
} from '@/features/webhooks/components/WebhookSecret.tsx'
import {
    ALL_EVENTS,
    type Webhook,
    webhookName,
} from '@/features/webhooks/types.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import {
    Form,
    FormButton,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { InputForm, SwitchForm } from '@/components/ui/Forms'
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
    /** The webhook being edited, `'new'` for the create flow, `null` when closed. */
    target: Webhook | 'new' | null
    close: () => void
}

const valuesFor = (webhook: Webhook | null): WebhookInput => ({
    url: webhook?.url ?? '',
    description: webhook?.description ?? '',
    allEvents: webhook ? webhook.events.includes(ALL_EVENTS) : false,
    events: webhook?.events.filter(entry => entry !== ALL_EVENTS) ?? [],
    enabled: webhook?.enabled ?? true,
})

/**
 * One dialog for both create and edit. Creating ends on the signing secret,
 * the only time it is shown, the way the token create dialog ends on the token.
 */
const WebhookFormDialog = ({ target, close }: Props) => {
    const current = target === 'new' ? null : target
    const queryClient = useQueryClient()
    const mutateLists = useQueryMutator(webhookQueries.lists())
    const [secret, setSecret] = useState<string | null>(null)

    const form = useForm<WebhookInput>({
        resolver: zodResolver(webhookSchema),
        values: valuesFor(current),
    })

    const allEvents = form.watch('allEvents')

    const { mutateAsync: save } = useMutation({
        mutationFn: (data: WebhookInput) =>
            current ? updateWebhook(current.uuid, data) : createWebhook(data),
    })

    const submit = async (data: WebhookInput) => {
        try {
            const { secret: created, ...webhook } = await save(data)

            queryClient.setQueryData(
                webhookQueries.detail(webhook.uuid).queryKey,
                webhook
            )
            await mutateLists()

            if (!current && created) {
                setSecret(created)
                toast.add({ title: 'Webhook added', type: 'success' })

                return
            }

            toast.add({ title: 'Webhook updated', type: 'success' })
            close()
        } catch (error) {
            handleFormErrors(error, form.setError)
            toast.add({ title: 'Failed to save webhook', type: 'error' })
        }
    }

    const onOpenChange = (open: boolean) => {
        if (open) return

        close()
        // Cleared after the close animation so the secret doesn't flash back
        // to the form on the way out.
        setTimeout(() => setSecret(null), 200)
    }

    return (
        <ResponsiveDialog open={target !== null} onOpenChange={onOpenChange}>
            <ResponsiveDialogContent className={'@container sm:max-w-2xl'}>
                {secret ? (
                    <>
                        <ResponsiveDialogHeader>
                            <ResponsiveDialogTitle>
                                Webhook added
                            </ResponsiveDialogTitle>
                            <ResponsiveDialogDescription>
                                {SECRET_DESCRIPTION}
                            </ResponsiveDialogDescription>
                        </ResponsiveDialogHeader>
                        <ResponsiveDialogBody>
                            <SecretValue secret={secret} />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter>
                            <ResponsiveDialogClose
                                render={<Button>Done</Button>}
                            />
                        </ResponsiveDialogFooter>
                    </>
                ) : (
                    <>
                        <ResponsiveDialogHeader>
                            <ResponsiveDialogTitle>
                                {current
                                    ? `Edit ${webhookName(current)}`
                                    : 'Add webhook'}
                            </ResponsiveDialogTitle>
                        </ResponsiveDialogHeader>
                        <Form {...form}>
                            <form onSubmit={form.handleSubmit(submit)}>
                                <ResponsiveDialogBody
                                    className={'flex flex-col gap-5'}
                                >
                                    <InputForm
                                        name={'url'}
                                        label={'URL'}
                                        type={'url'}
                                        autoComplete={'off'}
                                        placeholder={
                                            'https://example.com/webhooks/convoy'
                                        }
                                    />
                                    <InputForm
                                        name={'description'}
                                        label={'Description'}
                                        autoComplete={'off'}
                                    />
                                    <SwitchForm
                                        name={'enabled'}
                                        label={'Enabled'}
                                    />
                                    <SwitchForm
                                        name={'allEvents'}
                                        label={'All events'}
                                    />
                                    {!allEvents && (
                                        <FormField
                                            name={'events'}
                                            render={({ field, formState }) => (
                                                <FormItem>
                                                    <FormLabel>
                                                        Events
                                                    </FormLabel>
                                                    <EventPicker
                                                        value={field.value}
                                                        onChange={value =>
                                                            field.onChange(
                                                                value
                                                            )
                                                        }
                                                        disabled={
                                                            formState.isSubmitting
                                                        }
                                                    />
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    )}
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
                                    <FormButton>
                                        {current ? 'Save' : 'Add webhook'}
                                    </FormButton>
                                </ResponsiveDialogFooter>
                            </form>
                        </Form>
                    </>
                )}
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default WebhookFormDialog
