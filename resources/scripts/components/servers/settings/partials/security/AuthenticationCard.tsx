import FormCard from '@/components/elements/FormCard'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import SegmentedControl from '@/components/elements/SegmentedControl'
import Button from '@/components/elements/Button'
import { ServerContext } from '@/state/server'
import { useFlashKey } from '@/util/useFlash'
import { useEffect } from 'react'
import useSWR from 'swr'
import getSecurity from '@/api/server/settings/getSecurity'
import updateSecurity from '@/api/server/settings/updateSecurity'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { englishKeyboardCharacters, password } from '@/util/validation'
import { FormProvider, useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import TextareaForm from '@/components/elements/forms/TextareaForm'

const AuthenticationCard = () => {
    const { t: tStrings } = useTranslation('strings')
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(`servers.${uuid}.settings.security.auth`)

    const { data, mutate } = useSWR(['server:settings:security', uuid], () => getSecurity(uuid))

    const sshKeysSchema = z.object({
        type: z.literal('ssh_keys'),
        sshKeys: z.string().optional(),
    })

    const passwordSchema = z.object({
        type: z.literal('password'),
        sshKeys: z.string().optional(),
        password: englishKeyboardCharacters(password()),
    })
    const schema = z.discriminatedUnion('type', [sshKeysSchema, passwordSchema])

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            type: 'password',
            sshKeys: data?.sshKeys,
            password: '',
        },
    })

    const watchType = form.watch('type', 'password')

    const submit = async (values: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            await updateSecurity(uuid, {
                type: values.type,
                password: values.type === 'password' ? values.password : undefined,
                sshKeys: values.type === 'ssh_keys' ? values.sshKeys : undefined,
            })

            form.reset({
                sshKeys: values.sshKeys,
            })
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    useEffect(() => {
        form.reset({
            sshKeys: data?.sshKeys,
        })
    }, [data])

    return (
        <FormCard className='w-full'>
            <FormProvider {...form}>
                {/* @ts-ignore */}
                <form onSubmit={form.handleSubmit(submit)}>
                    <FormCard.Body>
                        <FormCard.Title>{tStrings('authentication')}</FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender byKey={`servers.${uuid}.settings.security.auth`} />
                            <SegmentedControl
                                className='!w-full md:!w-auto'
                                disabled={form.formState.isSubmitting}
                                value={watchType}
                                onChange={val => form.setValue('type', val)}
                                data={[
                                    { value: 'password', label: tStrings('password') },
                                    { value: 'ssh_keys', label: tStrings('ssh_key', { count: 2 }) },
                                ]}
                            />
                            {watchType === 'password' && (
                                <TextInputForm type='password' name='password' label='Password' />
                            )}
                            {watchType === 'ssh_keys' && <TextareaForm name='sshKeys' label='SSH Keys' />}
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button
                            loading={form.formState.isSubmitting}
                            type='submit'
                            variant='filled'
                            color='success'
                            size='sm'
                        >
                            {tStrings('save')}
                        </Button>
                    </FormCard.Footer>
                </form>
            </FormProvider>
        </FormCard>
    )
}

export default AuthenticationCard
