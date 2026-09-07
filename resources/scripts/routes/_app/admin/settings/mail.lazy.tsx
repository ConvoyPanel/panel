import {
    type MailSettingsForm,
    mailSettingsQuery,
    mailSettingsSchema,
    testMailSettings,
    updateMailSettings,
    useMailSettings,
} from '@/features/settings/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck, IconSend } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { FieldGroup } from '@/components/ui/Field'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, SelectForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

const ENCRYPTION_ITEMS = [
    { value: 'tls', label: 'STARTTLS (port 587)' },
    { value: 'ssl', label: 'Implicit TLS (port 465)' },
    { value: 'none', label: 'None (port 25)' },
]

const EMPTY_FORM: MailSettingsForm = {
    host: '',
    port: 587,
    username: '',
    password: '',
    encryption: 'tls',
    fromAddress: '',
    fromName: '',
}

/**
 * Pulls the transport's own words out of a failed test. "535 Authentication failed" and
 * "Connection could not be established" are each a different afternoon, and collapsing them
 * into "test failed" would leave the operator exactly where they were before this screen
 * existed.
 */
const transportError = (error: unknown): string => {
    const message = (
        error as { response?: { data?: { message?: string } } } | undefined
    )?.response?.data?.message

    return message || 'The test message could not be sent.'
}

const MailSettingsPage = () => {
    const { data: settings, isLoading } = useMailSettings()
    const mutateSettings = useQueryMutator(mailSettingsQuery().queryKey)

    const form = useForm<MailSettingsForm>({
        resolver: zodResolver(mailSettingsSchema),
        defaultValues: EMPTY_FORM,
    })

    useEffect(() => {
        if (!settings) return

        form.reset({
            host: settings.host,
            port: settings.port,
            username: settings.username,
            // Always blank: the API is write-only on this field, so there is
            // nothing to put here. Leaving it alone keeps the stored secret.
            password: '',
            encryption: settings.encryption,
            fromAddress: settings.fromAddress,
            fromName: settings.fromName,
        })
    }, [form, settings])

    // Sending the password key at all is what distinguishes "keep what is stored"
    // from "clear it", so it has to come from whether the field was edited rather
    // than from its value.
    const passwordTouched = () => form.getFieldState('password').isDirty

    const { mutateAsync: save } = useMutation({
        mutationFn: updateMailSettings,
        onSuccess: async updated => {
            await mutateSettings(() => updated)
            form.resetField('password', { defaultValue: '' })
            toast.add({ title: 'Mail settings updated', type: 'success' })
        },
    })

    const { mutateAsync: sendTest, isPending: isTesting } = useMutation({
        mutationFn: testMailSettings,
    })

    const submit = async (payload: MailSettingsForm) => {
        try {
            await save({ payload, includePassword: passwordTouched() })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({
                title: 'Failed to update mail settings',
                type: 'error',
            })
            console.error(e)
        }
    }

    const test = async () => {
        // Validate first: a test that fails because the form is half-filled would
        // read as a broken relay.
        if (!(await form.trigger())) return

        try {
            const { recipient } = await sendTest({
                payload: form.getValues(),
                includePassword: passwordTouched(),
            })

            toast.add({
                title: `Test message sent to ${recipient}`,
                type: 'success',
            })
        } catch (e) {
            toast.add({
                title: 'Test message failed',
                description: transportError(e),
                type: 'error',
            })
            console.error(e)
        }
    }

    if (isLoading) {
        return (
            <div className={'mx-auto w-full max-w-4xl space-y-4'}>
                <Heading>Mail</Heading>
                <Skeleton className={'h-96'} />
            </div>
        )
    }

    /*
     * No status banner and no "not configured" chip. An empty host *is* not configured, so a
     * label above the form only restates the field below it — and nothing here has failed, which
     * is what a coloured indicator would claim. The place that warning earns its weight is the
     * screen where unconfigured mail actually costs someone something; see
     * features/settings/components/MailNotConfiguredAlert.
     *
     * Capped at max-w-4xl like the node settings form: AppLayout gives the page up to 1600px, and
     * a form stretched that far pulls every label away from its control.
     */
    return (
        <div className={'mx-auto w-full max-w-4xl space-y-4'}>
            <Heading>Mail</Heading>
            <Form {...form}>
                <form
                    className={'space-y-4'}
                    onSubmit={form.handleSubmit(submit)}
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>SMTP server</CardTitle>
                            <CardDescription>
                                The relay Convoy sends through.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <FieldGroup>
                                {/* Host takes twice the port's width — a hostname is long and a
                                    port is four digits, so an even split wastes one and cramps
                                    the other. */}
                                <div
                                    className={'grid grid-cols-[2fr_1fr] gap-3'}
                                >
                                    <InputForm
                                        name={'host'}
                                        label={'Host'}
                                        placeholder={'smtp.example.com'}
                                    />
                                    <InputForm
                                        name={'port'}
                                        label={'Port'}
                                        type={'number'}
                                        inputMode={'numeric'}
                                    />
                                </div>
                                <SelectForm
                                    name={'encryption'}
                                    label={'Encryption'}
                                    items={ENCRYPTION_ITEMS}
                                />
                                <div className={'grid grid-cols-2 gap-3'}>
                                    <InputForm
                                        name={'username'}
                                        label={'Username'}
                                        autoComplete={'off'}
                                        description={
                                            "Leave empty if the relay doesn't require authentication."
                                        }
                                    />
                                    <InputForm
                                        name={'password'}
                                        label={'Password'}
                                        type={'password'}
                                        autoComplete={'new-password'}
                                        placeholder={
                                            settings?.passwordSet
                                                ? '••••••••••••'
                                                : undefined
                                        }
                                        description={
                                            settings?.passwordSet
                                                ? 'Leave blank to keep the stored password.'
                                                : 'Stored encrypted. Never shown again.'
                                        }
                                    />
                                </div>
                            </FieldGroup>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Sender</CardTitle>
                            <CardDescription>
                                Must be an address the server above is allowed
                                to send as.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <FieldGroup>
                                <div className={'grid grid-cols-2 gap-3'}>
                                    <InputForm
                                        name={'fromAddress'}
                                        label={'From address'}
                                        placeholder={'panel@example.com'}
                                    />
                                    <InputForm
                                        name={'fromName'}
                                        label={'From name'}
                                        placeholder={'Convoy'}
                                    />
                                </div>
                            </FieldGroup>
                        </CardContent>
                        {/* The form spans both cards and submits once, so the actions live in the
                            last card's footer rather than loose on the page background. */}
                        <CardFooter className={'justify-end gap-2'}>
                            <Button
                                type={'button'}
                                variant={'outline'}
                                loading={isTesting}
                                onClick={test}
                            >
                                Send test email{' '}
                                <IconSend className={'size-4'} />
                            </Button>
                            <FormButton className={'flex'}>
                                Save changes <IconCheck className={'size-4'} />
                            </FormButton>
                        </CardFooter>
                    </Card>
                </form>
            </Form>
        </div>
    )
}

export const Route = createLazyFileRoute('/_app/admin/settings/mail')({
    component: MailSettingsPage,
})
