import { ServerContext } from '@/state/server'
import { useFlashKey } from '@/util/useFlash'
import { ipAddress } from '@/util/validation'
import { TrashIcon } from '@heroicons/react/20/solid'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { FormProvider, useFieldArray, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import useSWR from 'swr'
import { z } from 'zod'

import getNetwork from '@/api/server/settings/getNetworkSettings'
import updateNetwork from '@/api/server/settings/updateNetworkSettings'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import TextInputForm from '@/components/elements/forms/TextInputForm'


const NameserversCard = () => {
    const { t: tStrings } = useTranslation('strings')
    const server = ServerContext.useStoreState(state => state.server.data!)

    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `servers.${server.uuid}.settings.network.nameservers`
    )

    const { data, mutate } = useSWR(
        ['server:settings:hardware', server.uuid],
        () => getNetwork(server.uuid)
    )

    const schema = z.object({
        nameservers: z
            .object({
                value: ipAddress(),
            })
            .array(),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            nameservers:
                data?.nameservers.map(nameserver => ({
                    value: nameserver,
                })) ?? [],
        } as unknown as z.infer<typeof schema>,
    })

    const { fields, append, remove } = useFieldArray({
        control: form.control,
        name: 'nameservers',
    })

    const submit = async (values: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            await updateNetwork(
                server.uuid,
                values.nameservers.map(nameserver => nameserver.value)
            )

            form.reset(values)
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    useEffect(() => {
        form.reset({
            nameservers:
                data?.nameservers.map(nameserver => ({
                    value: nameserver,
                })) ?? [],
        } as unknown as z.infer<typeof schema>)
    }, [data])

    return (
        <>
            <FormCard className='w-full'>
                <FormProvider {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <FormCard.Body>
                            <FormCard.Title>
                                {tStrings('nameserver', { count: 2 })}
                            </FormCard.Title>
                            <div className='space-y-3 mt-3'>
                                <FlashMessageRender
                                    byKey={`servers.${server.uuid}.settings.network.nameservers`}
                                />
                                <div className='flex flex-col space-y-3'>
                                    {fields.map((field, index) => (
                                        <TextInputForm
                                            key={field.id}
                                            disabled={
                                                form.formState.isSubmitting
                                            }
                                            name={`nameservers.${index}.value`}
                                            label={`${tStrings('nameserver', {
                                                count: 1,
                                            })} ${index + 1}`}
                                            rightSection={
                                                <button
                                                    type='button'
                                                    onClick={() =>
                                                        remove(index)
                                                    }
                                                    className='bg-transparent'
                                                >
                                                    <TrashIcon className='text-accent-400 w-4 h-4' />
                                                </button>
                                            }
                                        />
                                    ))}

                                    {form.getValues().nameservers.length <
                                        2 && (
                                        <Button
                                            type='button'
                                            onClick={() =>
                                                append({
                                                    value: '',
                                                })
                                            }
                                        >
                                            {tStrings('add')}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </FormCard.Body>
                        <FormCard.Footer>
                            <Button
                                disabled={!form.formState.isDirty}
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
        </>
    )
}

export default NameserversCard