import Modal from '@/components/elements/Modal'
import FormCard from '@/components/elements/FormCard'
import Button from '@/components/elements/Button'
import { useState } from 'react'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'
import useFlash, { useFlashKey } from '@/util/useFlash'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import TemplatesSelectFormik from '@/components/servers/settings/TemplatesSelectFormik'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'
import CheckboxFormik from '@/components/elements/formik/CheckboxFormik'
import reinstallServer from '@/api/server/settings/reinstallServer'
import { ServerContext } from '@/state/server'

const ReinstallServerContainer = () => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('server:settings:general:reinstall')
    const [isModalOpen, setIsModalOpen] = useState(false)
    const server = ServerContext.useStoreState(state => state.server.data!)
    const setServer = ServerContext.useStoreActions(state => state.server.setServer)
    const form = useFormik({
        initialValues: {
            templateUuid: '',
            accountPassword: '',
            startOnCompletion: false,
        },
        validationSchema: yup.object({
            templateUuid: yup.string().required('A template is required'),
            accountPassword: yup
                .string()
                .matches(
                    /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/,
                    'Must Contain 8 Characters, One Uppercase, One Lowercase, One Number and One Special Case Character'
                )
                .required(),
        }),
        onSubmit: async (values, { setSubmitting }) => {
            setSubmitting(true)
            clearFlashes()
            try {
                await reinstallServer(server.uuid, values)

                setServer({
                    ...server,
                    status: 'installing',
                })
            } catch (error) {
                clearAndAddHttpError(error as any)
            }
            setSubmitting(false)
        },
    })

    const handleCancel = () => {
        form.setSubmitting(false)
        setIsModalOpen(false)
    }

    return (
        <>
            <Modal open={isModalOpen} onClose={handleCancel}>
                <Modal.Header>
                    <Modal.Title>Reinstall Server?</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description bottomMargin>
                        Are you sure you want to reinstall this server? This is a destructive action and will remove all
                        data on your server.
                    </Modal.Description>
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={() => setIsModalOpen(false)}>Cancel</Modal.Action>
                    <Modal.Action loading={form.isSubmitting} onClick={form.submitForm}>
                        Reinstall
                    </Modal.Action>
                </Modal.Actions>
            </Modal>
            <FormikProvider value={form}>
                <FormCard className='w-full'>
                    <FormCard.Body>
                        <FormCard.Title>Reinstall Server</FormCard.Title>
                        <FlashMessageRender className='mt-3' byKey='server:settings:general:reinstall' />
                        <p className='description-small mt-3'>
                            Start your server on a fresh slate. Select a template and confirm the reinstallation.
                        </p>
                        <TemplatesSelectFormik disabled={form.isSubmitting} />
                        <TextInputFormik name={'accountPassword'} label={'System OS Password'} type={'password'} />
                        <CheckboxFormik
                            name={'startOnCompletion'}
                            label={'Start Server After Completion'}
                            className={'mt-3 relative'}
                        />
                    </FormCard.Body>
                    <FormCard.Footer className='flex justify-center md:justify-end'>
                        <Button
                            loading={form.isSubmitting}
                            disabled={!form.isValid || !form.touched}
                            onClick={() => setIsModalOpen(true)}
                            type={'submit'}
                            variant='filled'
                            color='danger'
                            size='sm'
                        >
                            Reinstall
                        </Button>
                    </FormCard.Footer>
                </FormCard>
            </FormikProvider>
        </>
    )
}

export default ReinstallServerContainer
