import { ServerContext } from '@/state/server'
import { useFlashKey } from '@/util/useFlash'
import { password, usKeyboardCharacters } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect, useState } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import useSWR from 'swr'
import { z } from 'zod'

import getSecurity from '@/api/server/settings/getAuthSettings'
import updateSecurity from '@/api/server/settings/updateAuthSettings'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import SegmentedControl from '@/components/elements/SegmentedControl'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import TextareaForm from '@/components/elements/forms/TextareaForm'


const AuthenticationCard = () => {
    const { t: tStrings } = useTranslation('strings')
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `servers.${uuid}.settings.security.auth`
    )
    const [type, setType] = useState<'password' | 'ssh_keys'>('password')

    const { data, mutate } = useSWR(['server:settings:security', uuid], () =>
        getSecurity(uuid)
    )

    const sshKeysSchema = z.object({
        sshKeys: z.string().optional(),
        password: z.string().optional(),
    })

    const passwordSchema = z.object({
        sshKeys: z.string().optional(),
        password: usKeyboardCharacters(password()),
    })
    const schema = type === 'password' ? passwordSchema : sshKeysSchema

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            sshKeys: data?.sshKeys ?? '',
            password: '',
        },
    })

    const submit = async (values: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            await updateSecurity(uuid, {
                type,
                password: type === 'password' ? values.password : undefined,
                sshKeys: type === 'ssh_keys' ? values.sshKeys : undefined,
            })

            form.reset({
                sshKeys: values.sshKeys ?? '',
                password: '',
            })
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    useEffect(() => {
        form.reset({
            sshKeys: data?.sshKeys ?? '',
            password: '',
        })
    }, [data])

    return (
        <FormCard className='w-full'>
            <FormProvider {...form}>
                {/* @ts-ignore */}
                <form onSubmit={form.handleSubmit(submit)}>
                    <FormCard.Body>
                        <FormCard.Title>
                            {tStrings('authentication')}
                        </FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender
                                byKey={`servers.${uuid}.settings.security.auth`}
                            />
                            <SegmentedControl
                                className='!w-full md:!w-auto'
                                disabled={form.formState.isSubmitting}
                                value={type}
                                onChange={val => setType(val as typeof type)}
                                data={[
                                    {
                                        value: 'password',
                                        label: tStrings('password'),
                                    },
                                    {
                                        value: 'ssh_keys',
                                        label: tStrings('ssh_key', {
                                            count: 2,
                                        }),
                                    },
                                ]}
                            />
                            {type === 'password' && (
                                <TextInputForm
                                    type='password'
                                    name='password'
                                    label='Password'
                                />
                            )}
                            {type === 'ssh_keys' && (
                                <TextareaForm name='sshKeys' label='SSH Keys' />
                            )}
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button
                            loading={form.formState.isSubmitting}
                            type='submit'
                            variant='filled'
                            color='success'
                            size='sm'
                            disabled={!form.formState.isDirty}
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
