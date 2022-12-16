import getNetwork from '@/api/server/settings/getNetwork'
import updateNetwork from '@/api/server/settings/updateNetwork'
import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import FormSection from '@/components/elements/FormSection'
import TextInput from '@/components/elements/inputs/TextInput'
import { ServerContext } from '@/state/server'
import useFlash from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { TrashIcon } from '@heroicons/react/20/solid'
import { FieldArray, FormikProvider, useFormik } from 'formik'
import useSWR from 'swr'
import * as yup from 'yup'

const NetworkingContainer = () => {
    const server = ServerContext.useStoreState(state => state.server.data!)

    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const notify = useNotify()

    const { data, mutate } = useSWR(['server:settings:hardware', server.uuid], () => getNetwork(server.uuid))

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            nameservers: data?.nameservers || [],
        },
        validationSchema: yup.object({
            nameservers: yup.array().of(
                yup
                    .string()
                    .matches(/(^(\d{1,3}\.){3}(\d{1,3})$)/, 'Invalid IPv4')
                    .required('A nameserver is required')
            ),
        }),
        onSubmit: ({ nameservers }, { setSubmitting }) => {
            clearFlashes('server:settings:networking')

            updateNetwork(server.id, nameservers)
                .then(() => {
                    notify({
                        title: 'Updated',
                        message: 'Updated network settings',
                        color: 'green',
                    })
                    mutate(data => ({ ...data, nameservers }), false)
                    setSubmitting(false)
                })
                .catch(error => {
                    clearAndAddHttpError({ key: 'server:settings:networking', error })
                    setSubmitting(false)
                })
        },
    })

    return (
        <FormSection title='Networking'>
            <FormCard className='w-full'>
                <FormikProvider value={form}>
                    <form onSubmit={form.handleSubmit}>
                        <FormCard.Body>
                            <FormCard.Title>Nameservers</FormCard.Title>
                            <div className='space-y-3 mt-3'>
                                <FlashMessageRender byKey='server:settings:networking' />
                                <div className='flex flex-col space-y-3'>
                                    <FieldArray
                                        name='nameservers'
                                        render={arrayHelpers => (
                                            <>
                                                {form.values.nameservers.map((nameserver, idx) => (
                                                    <TextInputFormik
                                                        key={idx}
                                                        disabled={form.isSubmitting}
                                                        name={`nameservers[${idx}]`}
                                                        label={`Nameserver ${idx + 1}`}
                                                        suffix={
                                                            <button
                                                                type='button'
                                                                onClick={() => arrayHelpers.remove(idx)}
                                                                className='bg-transparent'
                                                            >
                                                                <TrashIcon className='text-accent-400 w-4 h-4' />
                                                            </button>
                                                        }
                                                    />
                                                ))}

                                                {form.values.nameservers.length < 2 && (
                                                    <Button type='button' onClick={() => arrayHelpers.push('')}>
                                                        New Nameserver
                                                    </Button>
                                                )}
                                            </>
                                        )}
                                    />
                                </div>
                            </div>
                        </FormCard.Body>
                        <FormCard.Footer>
                            <Button
                                loading={form.isSubmitting}
                                type='submit'
                                variant='filled'
                                color='success'
                                size='sm'
                            >
                                Save
                            </Button>
                        </FormCard.Footer>
                    </form>
                </FormikProvider>
            </FormCard>
        </FormSection>
    )
}

export default NetworkingContainer
