import renameServer from '@/api/server/settings/renameServer'
import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import TextInput from '@/components/elements/inputs/TextInput'
import Modal from '@/components/elements/Modal'
import { ServerContext } from '@/state/server'
import useFlash from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { useFormik } from 'formik'
import { useState } from 'react'
import * as yup from 'yup'
import ReinstallServerContainer from '@/components/servers/settings/ReinstallServerContainer'

const GeneralContainer = () => {
    const server = ServerContext.useStoreState(state => state.server.data!)
    const setServer = ServerContext.useStoreActions(actions => actions.server.setServer)
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const notify = useNotify()

    const form = useFormik({
        initialValues: {
            name: server.name,
            hostname: server.hostname,
        },
        validationSchema: yup.object({
            name: yup.string().required('A name is required').max(40),
            hostname: yup
                .string()
                .matches(
                    /((https?):\/\/)?(www.)?[a-z0-9]+(\.[a-z]{2,}){1,3}(#?\/?[a-zA-Z0-9#]+)*\/?(\?[a-zA-Z0-9-_]+=[a-zA-Z0-9-%]+&?)?$/,
                    'Enter a valid hostname'
                ),
        }),
        onSubmit: ({ name, hostname }, { setSubmitting }) => {
            clearFlashes('server:settings:general:rename')

            renameServer(server.uuid, { name, hostname })
                .then(() => {
                    notify({
                        title: 'Updated',
                        message: 'Updated general settings',
                        color: 'green',
                    })
                    setServer({ ...server, name })
                    setSubmitting(false)
                })
                .catch(error => {
                    clearAndAddHttpError({ key: 'server:settings:general:rename', error })
                    setSubmitting(false)
                })
        },
    })

    return (
        <FormSection title='General Settings'>
            <FormCard className='w-full'>
                <form onSubmit={form.handleSubmit}>
                    <FormCard.Body>
                        <FormCard.Title>Server Name</FormCard.Title>
                        <div className='mt-3'>
                            <FlashMessageRender byKey='server:settings:general:rename' />
                            <TextInput
                                value={form.values.name}
                                onChange={form.handleChange}
                                error={form.touched.name ? form.errors.name : undefined}
                                disabled={form.isSubmitting}
                                name='name'
                                label='Display Name'
                            />
                            <TextInput
                                value={form.values.hostname}
                                onChange={form.handleChange}
                                error={form.touched.hostname ? form.errors.hostname : undefined}
                                disabled={form.isSubmitting}
                                name='hostname'
                                className='mt-3'
                                label='Hostname'
                            />
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button disabled={!form.dirty} loading={form.isSubmitting} type='submit' variant='filled' color='success' size='sm'>
                            Save
                        </Button>
                    </FormCard.Footer>
                </form>
            </FormCard>
            <ReinstallServerContainer />
        </FormSection>
    )
}

export default GeneralContainer
